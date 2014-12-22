<?php

$foo = 'foo';
echo $foo; // echoes 'foo'

$switch = new \ae\ValueSwitch($foo, 'bar');

echo $foo; // echoes 'bar'

unset($switch);

echo $foo; // echoes 'foo' again

