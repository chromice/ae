<?php

// Disable stuff
ini_set('html_errors', 'off');
xdebug_disable();


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
require 'ae/loader.php';

// Enable code coverage analyzer
ae::import(__DIR__ . '/ae/documentation.php');
$a = \ae\Documentation::analyzer();

// Route request
$request = ae::request();
$route = $request->route('/', '/examples/pages');

if ($route->exists())
{
	$route->follow();
}