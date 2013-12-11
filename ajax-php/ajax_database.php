<?php
/**
 * server
 * port
 * database
 * sql
 * fail
 *  status, contents, message
 * success
 *  status, contents, message
 */
include("ajax_secure.php");
 
$_KVP     = array_merge($_COOKIE, $_POST, $_GET);

$SERVER   = Ajax::arrayItem($_KVP, "server", "");
$PORT     = Ajax::arrayItem($_KVP, "port", 3306);
$DATABASE = Ajax::arrayItem($_KVP, "database", "");
$SQL      = Ajax::arrayItem($_KVP, "sql", "");


