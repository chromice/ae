<?php 

$general = ae::session();
$named = ae::session('foo');

if (!isset($general['foo']))
{
	echo '<h1>Initialise session variables</h1>';
	
	$general['foo'] = 0;
	
	$named['foo'] = 'NOT foo';
	$named['bar'] = 'bar';
}
else
{
	echo '<h1>Reusing session variables</h1>';
	
	$general['foo'] += 1;
	
	echo '<pre>$general[\'foo\'] = ' . ($general['foo']) . ';</pre>';
	
	foreach ($named as $key => $value)
	{
		echo '<pre>$named[\'' . $key . '\'] = "' . $value . '";</pre>';
	}
}

// Close sessions.
unset($named, $general);

// Open another session and close it.
$another = ae::session('foo');
$another['foo'] = 'NOT REALLY foo';
unset($another);

// Open one more session and close it.
$another = ae::session('foo');
$another['foo'] = 'foo';
unset($another);

