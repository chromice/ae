<?php

include 'ae/ae.php';

$module = new ae('module.examples', dirname(__FILE__));
	
$response = ae::load('response.php');
$request = ae::load('request.php');
$route = $request->route('/examples/');

if ($route->exists())
{
	$route->follow();
}
else
{
	$response->status(404);

	echo "<h1>Such page does not exist</h1>";
}
