<?php

$options = ae::load('options.php');
$options->set('bar','foo');
$optRef =& $options->reference();
$optRef['foo'] = 'bar';

$optRef2 =& $options->reference();
var_dump($optRef2);

?>