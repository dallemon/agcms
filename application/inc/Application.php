<?php namespace AGCMS;

use AGCMS\Controller\Base;
use AGCMS\Exception\InvalidInput;
use Closure;
use Raven_Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Application
{
    /** @var string */
    private $basePath;

    /** @var array[] */
    private $middleware = [];

    /** @var array[] */
    private $routes = [];

    /** @var Raven_Client */
    private $ravenClient;

    /** @var string[] */
    private $dontReport = [
        InvalidInput::class,
    ];

    /**
     * Set up the enviroment.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->ravenClient = new Raven_Client(Config::get('sentry'));
        $this->ravenClient->install();

        date_default_timezone_set(Config::get('timezone', 'Europe/Copenhagen'));

        if ('develop' === Config::get('enviroment', 'develop')) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        }

        setlocale(LC_ALL, Config::get('locale', 'C'));
        setlocale(LC_NUMERIC, 'C');

        bindtextdomain('agcms', $basePath . '/theme/locale');
        bind_textdomain_codeset('agcms', 'UTF-8');
        textdomain('agcms');

        mb_language('uni');
        mb_detect_order('UTF-8, ISO-8859-1');
        mb_internal_encoding('UTF-8');

        session_cache_limiter('');

        defined('_ROOT_') || define('_ROOT_', $basePath);
        $this->basePath = $basePath;
    }

    /**
     * Add middleware.
     *
     * @param string $uriPrefix
     * @param string $middleware
     *
     * @return void
     */
    public function addMiddleware(string $uriPrefix, string $middleware): void
    {
        $this->middleware[] = ['uriPrefix' => $uriPrefix, 'middleware' => $middleware];
    }

    /**
     * Add a route.
     *
     * @param string $method
     * @param string $uri
     * @param string $controller
     * @param string $action
     *
     * @return void
     */
    public function addRoute(string $method, string $uri, string $controller, string $action): void
    {
        $this->routes[$method][] = ['url' => $uri, 'controller' => $controller, 'action' => $action];
    }

    /**
     * Run the application.
     *
     * @param Request $request
     *
     * @return void
     */
    public function run(Request $request): void
    {
        Render::sendCacheHeader($request);
        try {
            $response = $this->dispatch($request);
        } catch (Throwable $exception) {
            $response = $this->handleException($request, $exception);
        }
        $response->prepare($request);
        $response->isNotModified($request); // Set up 304 response if relevant
        $response->send();
    }

    /**
     * Generate an error response and repport the exception.
     *
     * @param Request   $request
     * @param Throwable $exception
     *
     * @return Response
     */
    private function handleException(Request $request, Throwable $exception): Response
    {
        if ($this->shouldLog($exception)) {
            if ('develop' === Config::get('enviroment')) {
                throw $exception;
            }

            $this->ravenClient->captureException($exception);
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception->getCode() >= 400 && $exception->getCode() <= 599) {
            $status = $exception->getCode();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => ['message' => $exception->getMessage()]], $status);
        }

        return new Response($exception->getMessage(), $status);
    }

    /**
     * Determin if the exception should be logged.
     *
     * @param Throwable $exception
     *
     * @return bool
     */
    private function shouldLog(Throwable $exception): bool
    {
        foreach ($this->dontReport as $className) {
            if ($exception instanceof $className) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find a matching route for the current request.
     *
     * @param Request $request
     *
     * @return Response
     */
    private function dispatch(Request $request): Response
    {
        $redirect = $this->correctEncoding($request);
        if ($redirect) {
            return $redirect;
        }

        $metode = $request->getMethod();
        $requestUrl = urldecode($request->getPathInfo());
        $processRequest = $this->matchRoute($metode, $requestUrl);

        foreach ($this->middleware as $middleware) {
            if (0 === mb_strpos($requestUrl, $middleware['uriPrefix'])) {
                $processRequest = $this->wrapMiddleware($middleware['middleware'], $processRequest);
            }
        }

        return $processRequest($request);
    }

    /**
     * Wrap closure in a middle ware call.
     *
     * @param string $metode
     * @param string $requestUrl
     *
     * @return Closure
     */
    private function matchRoute(string $metode, string $requestUrl): Closure
    {
        foreach ($this->routes[$metode] as $route) {
            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl, $matches)) {
                return function (Request $request) use ($route, $matches): Response {
                    $matches[0] = $request;

                    return call_user_func_array([new $route['controller'](), $route['action']], $matches);
                };
            }

            if (preg_match('%^' . $route['url'] . '$%u', $requestUrl . '/', $matches)) {
                return $this->redirectToFolderPath($request, $requestUrl);
            }
        }

        return function (Request $request): RedirectResponse {
            return (new Base())->redirectToSearch($request);
        };
    }

    /**
     * Wrap closure in a middle ware call.
     *
     * @param string  $middlewareClass
     * @param Closure $next
     *
     * @return Closure
     */
    private function wrapMiddleware(string $middlewareClass, Closure $next): Closure
    {
        return function (Request $request) use ($middlewareClass, $next): Response {
            return (new $middlewareClass())->handle($request, $next);
        };
    }

    /**
     * Generate a redirect if URL was not UTF-8 encoded.
     *
     * @param Request $request
     *
     * @return ?RedirectResponse
     */
    private function correctEncoding(Request $request): ?RedirectResponse
    {
        $requestUrl = urldecode($request->getRequestUri());

        $encoding = mb_detect_encoding($requestUrl, 'UTF-8, ISO-8859-1');
        if ('UTF-8' === $encoding) {
            return null;
        }

        // Windows-1252 is a superset of iso-8859-1
        if (!$encoding || 'ISO-8859-1' === $encoding) {
            $encoding = 'windows-1252';
        }

        $requestUrl = mb_convert_encoding($requestUrl, 'UTF-8', $encoding);

        return (new Base())->redirect($request, $requestUrl, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Generate a redirect for the requested path with a / appended to the path.
     *
     * @param Request $request
     * @param string  $requestUrl
     *
     * @return RedirectResponse
     */
    private function redirectToFolderPath(Request $request, string $requestUrl): RedirectResponse
    {
        return function (Request $request) use ($requestUrl): RedirectResponse {
            $query = $request->getQueryString() ?: '';
            if ($query) {
                $query = '?' . $query;
            }

            return (new Base())->redirect($request, $requestUrl . '/' . $query, Response::HTTP_PERMANENTLY_REDIRECT);
        };
    }
}
