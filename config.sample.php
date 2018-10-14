<?php
require_once 'medoo.php';

$banlist = [];

$database = new medoo([
	'database_type' => 'mysql',
	'database_name' => '',
	'server'        => '',
	'username'      => '',
	'password'      => '',
	'charset'       => 'utf8',
]);

$header = "";
$footer = "";
?>
