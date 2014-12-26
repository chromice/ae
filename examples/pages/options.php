<?php

$options = ae::options('foo.bar')
	// ->set('goo', 'fail') // triggers an error
	->set('bar','foo')
	->set('foo','bar');

$options2 = ae::options('foo.bar', array(
	'bar' => 'nothing',
	'foo' => 'nada',
	'zar' => null
));

echo $options2->get('bar');
echo $options2->get('foo');
echo $options2->get('zar');

?>