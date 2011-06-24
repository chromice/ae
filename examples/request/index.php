<?php

$request = ae::load('request.php');

/*
	web or ajax request: index.php/segment-1/segment-2/segment-3?query=string
	
	cli request: index.php segment-1 segment-2 segment-3
*/

if ($request->is('ajax')) // or 'cli', or 'normal'
{
	echo "AJAX request";
}

echo $request->segment(0); // returns "index.php" or whatever file was called first
echo $request->segment(1,'default'); // returns "segment-1"
echo $request->segment(3,'default'); // returns "default"


$route = ae::load('route.php',$request->uri())
	->base('/request')
	->alias('uri/path','real/path');

if ($route->exists())
{
	// Load path for $request->uri()
	ae::load($route->path());
}
else
{
	ae::render('errors/404.php', array('uri' => $request->uri()));
}





