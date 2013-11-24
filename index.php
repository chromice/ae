<?php

include 'ae/core.php';

$request = ae::request();
$route = $request->route('/','/examples/pages/');

if ($route->exists())
{
	$route->follow();
}
else
{
	// ae::options('ae.response')->set('error_path', 'examples/error.php');
	ae::response()->error(404, 'examples/error.php');
}
