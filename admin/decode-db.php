<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<style type="text/css"><!--
* {
    font-size:14px;
}
--></style>
</head>

<body><?php

require_once '../inc/mysqli.php';

function htmlUrlDecode($text)
{
    global $mysqli;
    //TODO is this needed now that AJAX is used?
    if (get_magic_quotes_gpc()) {
        return $mysqli->real_escape_string(
            //atempt to make relative paths (generated by Firefox when copy pasting) in to absolute
            preg_replace(
                '/="[.]{2}\//iu',
                '="/',
                //TODO is this needed now that AJAX is used?
                stripslashes(
                    //Decode Firefox style urls
                    rawurldecode(
                        //Decode IE style urls
                        html_entity_decode(
                            //Double encode importand encodings, to survive next step and remove white space
                            preg_replace(
                                array(
                                    '/&lt;/u',
                                    '/&gt;/u',
                                    '/&amp;/u',
                                    "/\n/u",
                                    '/\s+/u'
                                ),
                                array(
                                    '&amp;lt;',
                                    '&amp;gt;',
                                    '&amp;amp;',
                                    ' ',
                                    ' '
                                ),
                                trim($text)
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    )
                )
            )
        );
    } else {
        return $mysqli->real_escape_string(
            //atempt to make relative paths (generated by Firefox when copy pasting) in to absolute
            preg_replace(
                array(
                    '/="[.]{2}\/(images)/iu',
                    '/="[.]{2}\/(files)/iu'
                ),
                '="/$1',
                //Decode Firefox style urls
                rawurldecode(
                    //Decode IE style urls
                    html_entity_decode(
                        //Double encode importand encodings, to survive next step and remove white space
                        preg_replace(
                            array(
                                '/&lt;/u',
                                '/&gt;/u',
                                '/&amp;/u',
                                "/\n/u",
                                '/\s+/'
                            ),
                            array(
                                '&amp;lt;',
                                '&amp;gt;',
                                '&amp;amp;',
                                ' ',
                                ' '
                            ),
                            trim($text)
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                )
            )
        );
    }
}

//Open database
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

/*
function unhtmlentitiesUtf8($string)
{
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    // changing translation table to UTF-8
    foreach ( $trans_tbl as $key => $value ) {
        $trans_tbl[$key] = utf8_encode($value);
    }
    return strtr($string, $trans_tbl);
}
*/

$mysqli = new Simple_Mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$sider = $mysqli->fetchArray("SELECT id, text, beskrivelse FROM `sider` WHERE text != '' OR beskrivelse != ''");
$sider_nr = count($sider);
for ($i=0;$i<$sider_nr;$i++) {
    $mysqli->query("UPDATE `sider` SET `text` = '".htmlUrlDecode($sider[$i]['text'])."', `beskrivelse` = '".htmlUrlDecode($sider[$i]['beskrivelse'])."' WHERE `id` = ".$sider[$i]['id']." LIMIT 1");
    unset($sider[$i]);
    echo $i . ' - ';
}
$special = $mysqli->fetchArray("SELECT id, text FROM `special` WHERE text != ''");
$special_nr = count($special);
for ($i=0;$i<$special_nr;$i++) {
    $mysqli->query("UPDATE `special` SET `text` = '".htmlUrlDecode($special[$i]['text'])."' WHERE `id` = ".$special[$i]['id']." LIMIT 1");
    unset($special[$i]);
    echo $i . ' - ';
}
luk_forbindelse();
?>Done!
</body>
</html>