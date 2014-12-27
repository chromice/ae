<?php

/// require 'path/to/ae/loader.php';

ae::register('library', __DIR__ . '/library.php', array(
	'Library',
	'AnotherLibraryClass'
));

echo AnotherLibraryClass::bar(); // echo 'bar'

$lib = ae::library();

echo $lib->foo(); // echo 'foo'
echo $lib->bar(); // echo 'bar'

ae::import(__DIR__ . '/helper.php');

echo foo(); // echo 'foo'