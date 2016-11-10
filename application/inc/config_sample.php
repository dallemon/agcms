<?php
/**
 * Configuration of site
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     http://www.arms-gallery.dk/
 */

$GLOBALS['_config']['base_url'] = 'http://www.example.com';
$GLOBALS['_config']['site_name'] = 'My store';
$GLOBALS['_config']['address'] = '';
$GLOBALS['_config']['postcode'] = '';
$GLOBALS['_config']['city'] = '';
$GLOBALS['_config']['phone'] = '';
$GLOBALS['_config']['fax'] = '';

$GLOBALS['_config']['emails']['mail@example.com'] = [
    'address'  => 'mail@example.com',
    'password' => 'password',
    'sentBox'  => 'INBOX.Sent',
    'imapHost' => 'imap.example.dk',
    'imapPort' => 143,
    'smtpHost' => 'smtp.example.com',
    'smtpPort' => '25',
    'smtpAuth' => true,
];

$GLOBALS['_config']['interests'][] = 'Stuff';

$GLOBALS['_config']['pbsid'] = '';
$GLOBALS['_config']['pbspassword'] = '';
$GLOBALS['_config']['pbsfix'] = '';
$GLOBALS['_config']['pbswindow'] = 0;
$GLOBALS['_config']['pbspwd'] = '';
$GLOBALS['_config']['pbssalt'] = '';

$GLOBALS['_config']['mysql_server'] = 'db';
$GLOBALS['_config']['mysql_user'] = 'root';
$GLOBALS['_config']['mysql_password'] = '';
$GLOBALS['_config']['mysql_database'] = 'agcms';

//Admin options

//FTP for makedir
$GLOBALS['_config']['ftp_User'] = '';
$GLOBALS['_config']['ftp_Pass'] = '';
$GLOBALS['_config']['ftp_Root'] = '';

//Site color settings
$GLOBALS['_config']['bgcolor'] = "FFFFFF";
$GLOBALS['_config']['bgcolorR'] = 255;
$GLOBALS['_config']['bgcolorG'] = 255;
$GLOBALS['_config']['bgcolorB'] = 255;

//Images
$GLOBALS['_config']['thumb_width'] = 150;
$GLOBALS['_config']['thumb_height'] = 150;

$GLOBALS['_config']['text_width'] = 700;

$GLOBALS['_config']['frontpage_width'] = 700;
