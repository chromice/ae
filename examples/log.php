<?php
	
	ae::log("Hello world");
	
	trigger_error("You fucking suck", E_USER_ERROR);
	
	$container = ae::container('/examples/container/container_inner.php');
	$container
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
		
	ae::log("Hello kitty", 24, true);
?>
Team bravo.