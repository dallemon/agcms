<?php
/**
 * Handle SEO requests
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

//If url !utf-8 make it fucking utf-8 and try again
$url = urldecode($_SERVER['REQUEST_URI']);
//can't detect windows-1252
$encoding = mb_detect_encoding($url, 'UTF-8, ISO-8859-1');
if ($encoding != 'UTF-8') {
    //Firefox uses windows-1252 if it can get away with it
    /**
     * We can't detect windows-1252 from iso-8859-1, but it's a superset, so bouth
     * should handle fine as windows-1252
     */
    if (!$encoding || $encoding == 'ISO-8859-1') {
        $encoding = 'windows-1252';
    }
    $url = mb_convert_encoding($url, 'UTF-8', $encoding);
    //TODO rawurlencode $url (PIE doesn't do it buy it self :(
    $url = implode("/", array_map("rawurlencode", explode("/", $url)));

    ini_set('zlib.output_compression', '0');
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: '.$url);
    die();
}

if (preg_match('/(\=[^&].*)/u', $url)) {
    //is there more parameters after q?
    if (preg_match('/[q=.*&]/u', $url)) {
        $q = preg_replace('/.*q=(.*)[&]{1}.*|.*/u', '\1', $url);
    } else {
        $q = preg_replace('/.*q=(.*)$|.*/u', '\1', $url);
    }
}

$GLOBALS['generatedcontent']['activmenu'] = (int) preg_replace(
    '/.*\/kat([0-9]*)-.*|.*/u',
    '\1',
    $url
);
$redirect = false;
if (!$GLOBALS['generatedcontent']['activmenu']) {
    $categoryId = preg_replace('/.*kat=([0-9]+).*\s*|.*/u', '\1', $url);
    if ($categoryId) {
        $GLOBALS['generatedcontent']['activmenu'] = $categoryId;
        $redirect = true;
    }
}

//Try old query sting methode
$GLOBALS['side']['id'] = preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url);
if (!$GLOBALS['side']['id']) {
    $sideId = preg_replace('/.*side=([0-9]+).*\s*|.*/u', '\1', $url);
    //Try really old query sting methode
    if (!$sideId) {
        $sideId = preg_replace('/.*id=([0-9]+).*\s*|.*/u', '\1', $url);
    }

    if ($sideId) {
        $GLOBALS['side']['id'] = $sideId;
        $redirect = true;
    }
}

//Get maerke
if (empty($maerke)) {
    $maerke = preg_replace('/.*\/mærke([0-9]*)-.*|.*/u', '\1', $url);
}
if (!$maerke) {
    $maerke = preg_replace('/.*\/maerke([0-9]*)-.*|.*/u', '\1', $url);
    //TODO redirect to mærke
}

//Old url detected and redirect needed.
if ($redirect) {
    ini_set('zlib.output_compression', '0');
    header('HTTP/1.1 301 Moved Permanently');
    if ($GLOBALS['side']['id']) {
        if (!$GLOBALS['generatedcontent']['activmenu']) {
            $bind = db()->fetchOne(
                "
                SELECT kat
                FROM bind
                WHERE side = " . $GLOBALS['side']['id']
            );
            if (!$bind) {
                $url = '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=';
                header('Location: ' . $url);
                die();
            }
            $categoryId = ORM::getOne(Category::class, $bind['kat']);
        } else {
            $categoryId = ORM::getOne(Category::class, $GLOBALS['generatedcontent']['activmenu']);
        }
        $page = db()->fetchOne(
            "
            SELECT id, navn
            FROM sider
            WHERE id = " . $GLOBALS['side']['id']
        );
        if (!$page) {
            header('Location: /' . $categoryId->getSlug());
            die();
        }
        $url = '/' . $categoryId->getSlug() . 'side' . $page['id'] . '-' . clearFileName($page['navn']) . '.html';
        header('Location: ' . $url);
        die();
    } elseif ($GLOBALS['generatedcontent']['activmenu']) {
        $categoryId = ORM::getOne(Category::class, $GLOBALS['generatedcontent']['activmenu']);
        header('Location: /' . $categoryId->getSlug());
        die();
    }
}

if (empty($sog)
    && !$GLOBALS['generatedcontent']['activmenu']
    && !$GLOBALS['side']['id']
    && empty($q)
    && empty($maerke)
) {
    $q = trim(
        preg_replace(
            array (
                '/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|\.php/u',
                '/([0-9]+)/u',
                '/([[:upper:]]?[[:lower:]]+)/u',
                '/([\r\n])[\s]+/u'
            ),
            array (
                ' ',
                ' \1 ',
                ' \1',
                '\1'
            ),
            $url
        )
    );
    $GLOBALS['generatedcontent']['activmenu'] = -1;
    ini_set('zlib.output_compression', '0');
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /?q=".rawurlencode($q)."&sogikke=&minpris=&maxpris=&maerke=0");
    die();
}

//TODO stop space efter æøå
header("Status: 200", true, 200);
header("HTTP/1.1 200 OK", true, 200);
require 'index.php';