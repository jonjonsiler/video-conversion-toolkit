<?php
include_once("../configure.php");
//database server
$config->server = REP_HOST . ":" . REP_PORT;
$config->port	= REP_PORT;
//database login name
$config->user = REP_USER;
//database login password
$config->pass = REP_PASS;
//database name
$config->database = REP_DATABASE;
?>
