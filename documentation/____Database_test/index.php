<?php

/// >>> Configure
// Configure the "default" database connection
$connection = ae::options('ae::database(default)');

$connection['host'] = 'localhost';
$connection['user'] = 'root';
$connection['password'] = 'root';
$connection['database'] = 'ae';
/// <<<

/// >>> Query logging
$db_options = ae::options('ae::database');

$db_options['log'] = true;
/// <<<

/// >>> Make a query
try {
	$db = ae::database(); // same as ae::database("default");
	
	$db->query("SELECT 1")->make();
} catch (\ae\DatabaseException $e) {
	echo 'Something went wrong: ' . $e->getMessage();
}
/// <<<

