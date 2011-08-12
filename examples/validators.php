<?php

$validator = ae::load('validator.php')
	->natural('The velue must be a natural number.');
	->maxValue(1000, 'The velue must be less than 1000.')
	->minValue(1, 'The value must be greater than 0.')
	->custom('some_function', $param_1, $param_2); // Calls some_function($value, $param_1, $param_2)

$var = 1001;

$result = $validator->validate($var);

if ($result !== true)
{
	echo $result['constraint']; // maxValue
	echo $result['error']; // The value must be less than 1000.
	echo $result['value']; // 1001
	
	// Constraint parameters:
	echo $result[0]; // 1000
	// echo $result[1];
	// echo $result[2];
	// ...
}

?>