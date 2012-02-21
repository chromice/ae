<?php
	$container = ae::container('/examples/container/container_inner.php');
	$container
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
?>
<p>A simple nestable view heirarchy.</p>