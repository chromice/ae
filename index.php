<?php

// PHP's built-in web server
if (php_sapi_name() === 'cli-server'
&& !empty($_SERVER["REQUEST_URI"])
&& is_file(dirname(__FILE__) . $_SERVER["REQUEST_URI"])) 
{
	return false;
}

// Make sure default timezone is set
date_default_timezone_set(@date_default_timezone_get());

// Default code path
include 'ae/core.php';
use \ae\Core as ae;

// Enable code coverage analyzer
ae::import('ae/documentation.php');
$a = \ae\Documentation::analyzer();

// Route request
$request = ae::request();
$route = $request->route('/','/documentation');

if ($route->exists())
{
	$route->follow();
}