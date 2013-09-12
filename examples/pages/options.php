<?php

$options = ae::options('foo.bar')
	->set('bar','foo')
	->set('foo','bar');

$options2 = ae::options('foo.bar');

echo $options2->get('bar');
echo $options2->get('foo');

?>