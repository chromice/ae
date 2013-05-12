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
	header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 404 Not Found');

	echo "<h1>Such page does not exist</h1>";
}
