<?php

include 'ae/core.php';

$request = ae::request();
$route = $request->route('/','/examples/');

if ($route->exists())
{
	$route->follow();
}
else
{
	// ae::options('response')->set('error_path', 'examples/error.php');
	ae::response()->error(404, 'examples/error.php');
}
