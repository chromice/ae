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

// 
switch ($request->segment(0, null))
{
	case null:
		ae::output('/examples/pages/index.php');
		break;
	
	case 'docs':
		if ($request->segment(1, null) === null)
		{
			ae::output('/documentation/index.php');
			break;
		}
	
	case 'pages':
		if ($request->segment(1, null) === null)
		{
			$request->redirect('/');
		}
	
	default:
		$route = $request->route([
			'/pages' => '/examples/pages',
			'/' => '/documentation',
		]);
		
		if ($route->exists())
		{
			$route->follow();
		}
		break;
}
