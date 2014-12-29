<?php

/// require 'path/to/ae/loader.php';

ae::register(__DIR__ . '/library.php', array(
	'library',
	'foo'
), array(
	'\ns\Library',
	'\ns\AnotherLibraryClass'
));

echo AnotherLibraryClass::bar(); // echo 'bar'

$params = null;

$lib = ae::load('library', $params);
// or 
$lib = ae::library($params);

echo $lib->foo(); // echo 'foo'
echo $lib->bar(); // echo 'bar'

echo ae::foo()->foo(); // echo foo

ae::import(__DIR__ . '/helper.php');

echo bar(); // echo 'bar'