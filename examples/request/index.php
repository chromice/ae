<?php

$request = ae::load('request.php');
// or
$request = ae::load('request.php','index.php/segment-1/segment-2/segment-3?query=string');
// to specify uri manually

/*
	web or ajax request: index.php/segment-1/segment-2/segment-3?query=string
	
	cli request: index.php segment-1 segment-2 segment-3 --query=string
*/

if ($request->is('ajax')) // or 'cli', or 'normal'
{
	echo "AJAX request";
}

echo $request->segment(0); // returns "index.php" or whatever file was called first
echo $request->segment(1,'default'); // returns "segment-1"
echo $request->segment(3,'default'); // returns "default"

$route = $request->route('base/path', array(
	'alias/1' => 'some/path',
	'alias/2' => 'some/other/path'
));

if ($route->exists())
{
	echo $route->argument(0); // 'segment-1'
	
	ae::render($route->path()); 
	// loads a script, e.g. '/segment-1/segment-2.php' and
	// shifts the argument pointer offset from 'segment-1' to 'segment-3'
	
	echo $route->argument(0); // 'segment-3'
	
	$route2 = $request->route(); // returns the copy of the perviously created route object
	
	echo $route2->argument(0); // 'segment-3'
	echo $route2->argument(1,'default'); // 'default'
}
else
{
	ae::render('errors/404.php', array('uri' => $request->uri()));
}

