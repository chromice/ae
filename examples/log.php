<?php
	ae::import('log.php');
	
	ae::log("Hello world");
	
	trigger_error("This is some notice.", E_USER_NOTICE);
	
	$c = ae::container('/examples/container/container_inner.php')
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
		
	ae::log("Hello kitty. This is a number: ", 24, "And this is a boolean: ", true);
	
	$r = ae::request();
	
	switch ($r->segment(0, 'normal'))
	{
		case 'error':
			ae::render('examples/log/trigger_error.php');
			break;

		case 'exception':
			ae::render('examples/log/throw_exception.php', array(
				'foo' => 'bar',
				'bar' => 'foo'
			));
			break;
		
		case 'critical':
			ae::render('examples/log/shutdown_error.php', array(
				'foo' => 'bar',
				'bar' => 'foo'
			));
			break;
	}
?>
	<p>Team bravo.</p>
