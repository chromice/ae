<?php

ae::options('my_application', array(
	'title' => 'My awesome app',
	'version' => '0.93'
));

$o = ae::options('my_application');
echo $o->get('title') . ' v' . $o->get('version');