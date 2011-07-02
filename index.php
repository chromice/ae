<?php

include 'ae/ae.php';

$module = new ae('module.examples', dirname(__FILE__));
	
$response = ae::load('response.php');
$request = ae::load('request.php', !empty($_GET['uri']) ? $_GET['uri'] : '/');
$route = $request->route('/examples', array(
	'non-existant/uri' => 'request'
));

if ($route->exists())
{
	echo ae::render($route->path());
}
else
{
	$response->status(404);

	echo "<h1>Such page does not exist</h1>";
}
