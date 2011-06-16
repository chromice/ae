<?php

include 'ae/ae.php';

$module = new ae('ae/examples', dirname(__FILE__));
	
$response = ae::load('response.php');
$request = ae::load('request.php', !empty($_GET['uri']) ? $_GET['uri'] : '/');

if (!$request
	->alias('non-existant/uri','request')
	->routeIn('/examples'))
{
	$response->status(404);

	echo "Nothing found";
}
