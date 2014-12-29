<?php

/// require 'path/to/ae/loader.php';

/// >>> Registering
ae::register(__DIR__ . '/library.php', array(
	'library' => '\ns\Library',
	'\ns\AnotherLibraryClass'
));
/// <<< end

/// >>> Class loading
echo \ns\AnotherLibraryClass::bar(); // echo 'bar'
/// <<< end

$params = null;

/// >>> Loading
$lib = ae::load('library', $params);
// or 
$lib = ae::library($params);
/// <<<

/// >>> Using library
echo $lib->foo(); // echo 'foo'
echo $lib->bar(); // echo 'bar'
/// <<<

echo ae::foo()->foo(); // echo foo

/// >>> Importing
ae::import(__DIR__ . '/helper.php');
/// <<<

echo bar(); // echo 'bar'