<?php
/**
 * file - relative, absolute
 * format - always json
 *  fail
 *      status, contents, message
 * success
 *      status, contents, message
 */
include("ajax_secure.php");
 
$_KVP   = array_merge($_COOKIE, $_POST, $_GET);

$FILE   = Ajax::arrayItem($_KVP, "file", "");
$FORMAT = Ajax::arrayItem($_KVP, "format", "");

