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

echo 'Client IP address: ' . \ae\Request::ip_address() . "\n";
echo 'Server IP address: ' . $_SERVER['SERVER_ADDR'] . "\n\n";

echo \ae\Request::method . ' ' . \ae\Request::uri() . ' ' . \ae\Request::protocol . "\n";
echo 'Is CLI: ' . (\ae\Request::is_cli ? 'Yes' : 'No') . "\n";
echo 'Is AJAX: ' . (\ae\Request::is_ajax ? 'Yes' : 'No') . "\n";
echo 'Is Routed: ' . ($request->is_routed() ? 'Yes' : 'No') . "\n\n";

$segments = \ae\Request::segments();
var_dump($segments);

echo \ae\Request::type();
echo "\n";
echo $request->segment(0); // 'segment-1'
echo "\n";
echo $request->segment(1, 'default'); // 'segment-2'
echo "\n";
echo $request->segment(2, 'default'); // 'default'
echo "\n";
echo $request->segment(3, 'default'); // 'default'
echo "\n";

// Route request
// $route = $request->route('/', 'examples/request/views');
// or
$route = $request->route(array(
	'/view/{any}/{alpha}/{numeric}' => function ($s1, $s2, $number, $trailing) {
		echo 'Routing function called with arguments: ' . $s1 . ', ' . $s2 . ', ' . $number . ', ' . $trailing . '<br>';
	},
	'/' => 'examples/request/views'
));

if ($route->exists())
{
	$route->follow(); 
}
else
{
	ae::output('examples/request/views/404.php');
}

// Redirect request to a file
$route = $request->route('/', 'examples/request/views/view.php');

// It is pointless to check if path exists or not...
$route->follow(); 

echo "\n</pre>";

