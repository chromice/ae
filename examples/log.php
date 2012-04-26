<?php
register_shutdown_function('echoLogOnShutdown');

function echoLogOnShutdown()
{
	header('X-Produced-with: ae_framework');
	echo "<!--BRZ: message -->";
}
	// ae::log('Something happend.', $_SERVER);
?>
<?php
	$container = ae::container('/examples/container/container_inner.php');
	$container
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
?>
Team bravo.