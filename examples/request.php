<?php

$request = ae::request();
// or
// $request = ae::request('foo/bar/fubar');
// to specify uri manually

/*
	web or ajax request: index.php/segment-1/segment-2/segment-3
	
	cli request: index.php segment-1 segment-2 segment-3
*/
echo "<pre>\n";

echo 'Client IP address: ' . aeRequest::ip_address() . "\n";
echo 'Server IP address: ' . $_SERVER['SERVER_ADDR'] . "\n\n";

echo 'Request method: ' . aeRequest::method . "\n";
echo 'Is CLI: ' . (aeRequest::cli ? 'Yes' : 'No') . "\n";
echo 'Is AJAX: ' . (aeRequest::ajax ? 'Yes' : 'No') . "\n\n";

echo $request->type();
echo "\n";
echo $request->segment(0); // 'segment-1'
echo "\n";
echo $request->segment(1, 'default'); // 'segment-2'
echo "\n";
echo $request->segment(3, 'default'); // 'default'
echo "\n";

// Route request
$route = $request->route('examples/request/views');

if ($route->exists())
{
	$route->follow(); 
	// loads a script, e.g. '/segment-1/segment-2.php'
}
else
{
	ae::output('examples/request/views/404.php');
}

// Redirect request to a file
$route = $request->route('examples/request/views/view.php');

// It is pointless to check if path exists or not...
$route->follow(); 

echo "\n</pre>";

