<?php

// PHP's built-in web server
if (php_sapi_name() === 'cli-server'
&& !empty($_SERVER["REQUEST_URI"])
&& is_file(dirname(__FILE__) . $_SERVER["REQUEST_URI"])) 
{
	return false;
}

// Default code path
include 'ae/core.php';

$request = ae::request();
$route = $request->route('/','/examples/pages/');

if ($route->exists())
{
	$route->follow();
}
else
{
	ae::response()->error(404, 'examples/error.php');
}
