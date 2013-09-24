<?php 

$general = ae::session();
$named = ae::session('foo');

if (!isset($general['foo']))
{
	echo '<h1>Initialise session variables</h1>';
	
	$general['foo'] = 0;
	
	$named['foo'] = 'test';
	$named['bar'] = 'test2';
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


