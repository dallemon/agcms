<?php namespace AGCMS\Controller\Admin;

use AGCMS\Config;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Request;
use AGCMS\Service\InvoicePdfService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends AbstractAdminController
{
    /**
     * List of invoices.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $selected = [
            'id'         => (int) $request->get('id') ?: null,
            'year'       => $request->query->getInt('y', date('Y')),
            'month'      => $request->query->getInt('m'),
            'department' => $request->get('department'),
            'status'     => $request->get('status', 'activ'),
            'name'       => $request->get('name'),
            'tlf'        => $request->get('tlf'),
            'email'      => $request->get('email'),
            'momssats'   => $request->get('momssats'),
            'clerk'      => $request->get('clerk'),
        ];
        if (null === $selected['clerk'] && !$request->user()->hasAccess(User::ADMINISTRATOR)) {
            $selected['clerk'] = $request->user()->getFullName();
        }
        if ('' === $selected['momssats']) {
            $selected['momssats'] = null;
        }

        $where = [];

        if ($selected['month'] && $selected['year']) {
            $where[] = "`date` >= '" . $selected['year'] . '-' . $selected['month'] . "-01'";
            $where[] = "`date` <= '" . $selected['year'] . '-' . $selected['month'] . "-31'";
        } elseif ($selected['year']) {
            $where[] = "`date` >= '" . $selected['year'] . "-01-01'";
            $where[] = "`date` <= '" . $selected['year'] . "-12-31'";
        }

        if ($selected['department']) {
            $where[] = '`department` = ' . db()->eandq($selected['department']);
        }
        if ($selected['clerk']
            && (!$request->user()->hasAccess(User::ADMINISTRATOR) || $request->user()->getFullName() === $selected['clerk'])
        ) {
            //Viewing your self
            $where[] = '(`clerk` = ' . db()->eandq($selected['clerk']) . " OR `clerk` = '')";
        } elseif ($selected['clerk']) {
            //Viewing some one else
            $where[] = '`clerk` = ' . db()->eandq($selected['clerk']);
        }

        if ('activ' === $selected['status']) {
            $where[] = "`status` IN('new', 'locked', 'pbsok', 'pbserror')";
        } elseif ('inactiv' === $selected['status']) {
            $where[] = "`status` NOT IN('new', 'locked', 'pbsok', 'pbserror')";
        } elseif ($selected['status']) {
            $where[] = '`status` = ' . db()->eandq($selected['status']);
        }

        if ($selected['name']) {
            $where[] = "`navn` LIKE '%" . db()->esc($selected['name']) . "%'";
        }

        if ($selected['tlf']) {
            $where[] = "(`tlf1` LIKE '%" . db()->esc($selected['tlf'])
                . "%' OR `tlf2` LIKE '%" . db()->esc($selected['tlf']) . "%')";
        }

        if ($selected['email']) {
            $where[] = "`email` LIKE '%" . db()->esc($selected['email']) . "%'";
        }

        if ($selected['momssats']) {
            $where[] = '`momssats` = ' . db()->eandq($selected['momssats']);
        }

        $where = implode(' AND ', $where);

        if ($selected['id']) {
            $where = ' `id` = ' . $selected['id'];
        }

        $oldest = db()->fetchOne(
            "
            SELECT UNIX_TIMESTAMP(`date`) AS `date` FROM `fakturas`
            WHERE UNIX_TIMESTAMP(`date`) != '0' ORDER BY `date`
            "
        );
        $oldest = $oldest['date'] ?? time();
        $oldest = date('Y', $oldest);

        $invoices = ORM::getByQuery(Invoice::class, 'SELECT * FROM `fakturas` WHERE ' . $where . ' ORDER BY `id` DESC');

        $data = [
            'title'         => _('Invoice list'),
            'currentUser'   => $request->user(),
            'selected'      => $selected,
            'countries'     => include _ROOT_ . '/inc/countries.php',
            'departments'   => array_keys(Config::get('emails', [])),
            'users'         => ORM::getByQuery(User::class, 'SELECT * FROM `users` ORDER BY `fullname`'),
            'invoices'      => $invoices,
            'years'         => range($oldest, date('Y')),
            'statusOptions' => [
                ''         => 'All',
                'inactiv'  => _('Completed'),
                'new'      => _('New'),
                'locked'   => _('Locked'),
                'pbsok'    => _('Ready'),
                'accepted' => _('Expedited'),
                'giro'     => _('Giro'),
                'cash'     => _('Cash'),
                'pbserror' => _('Error'),
                'canceled' => _('Canceled'),
                'rejected' => _('Rejected'),
            ],
        ] + $this->basicPageData($request);

        $content = Render::render('admin/fakturas', $data);

        return new Response($content);
    }

    /**
     * List of invoices.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function validationList(Request $request): Response
    {
        $invoices = ORM::getByQuery(
            Invoice::class,
            "
            SELECT * FROM `fakturas`
            WHERE `transferred` = 0 AND `status` = 'accepted'
            ORDER BY `paydate` DESC, `id` DESC
            "
        );

        $data = [
            'title'    => _('Invoice validation'),
            'invoices' => $invoices,
        ] + $this->basicPageData($request);

        $content = Render::render('admin/fakturasvalidate', $data);

        return new Response($content);
    }

    /**
     * Set payment transferred status.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function validate(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->hasAccess(User::ADMINISTRATOR)) {
            throw new InvalidInput('You do not have permissions to validate payments!');
        }

        $invoice = ORM::getOne(Invoice::class, $id);
        assert($invoice instanceof Invoice);
        $invoice->setTransferred($request->request->getBoolean('transferred'))->save();

        return new JsonResponse([]);
    }

    /**
     * Create a new invoice.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $invoice = new Invoice(['clerk' => $request->user()->getFullName()]);
        $invoice->save();

        return new JsonResponse(['id' => $invoice->getId()]);
    }

    /**
     * Display invoice.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function invoice(Request $request, int $id): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        assert($invoice instanceof Invoice);

        if (!$invoice->getClerk()) {
            $invoice->setClerk($request->user()->getFullName());
        }

        $data = [
            'title'       => _('Online Invoice #') . $invoice->getId(),
            'status'      => $invoice->getStatus(),
            'currentUser' => $request->user(),
            'users'       => ORM::getByQuery(User::class, 'SELECT * FROM `users` ORDER BY fullname'),
            'invoice'     => $invoice,
            'departments' => array_keys(Config::get('emails', [])),
            'countries'   => include _ROOT_ . '/inc/countries.php',
        ] + $this->basicPageData($request);

        $content = Render::render('admin/faktura', $data);

        return new Response($content);
    }

    /**
     * Show a pdf version of the invoice.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function pdf(Request $request, int $id): Response
    {
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            return new Response(_('Invoice not found.'), 404);
        }

        $invoicePdfService = new InvoicePdfService();
        $pdfData = $invoicePdfService->createPdf($invoice);

        $header = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Faktura-' . $invoice->getId() . '.pdf"',
        ];

        return new Response($pdfData, 200, $header);
    }
}
