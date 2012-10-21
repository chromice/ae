<?php
	ae::import('ae/log.php');
	ae::options('log')
		// ->set('directory', '/examples/log')
		->set('environment', true)
		->set('console', true);
	
	ae::log("Error: This is not really an error!", array());
	
	trigger_error("This is some notice.", E_USER_NOTICE);
	
	$c = ae::container('/examples/container/container_inner.php')
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
	
	ae::log("Hello kitty. This is a number: ", 24, "And this is a boolean: ", true, ' And a bit of void: ', NULL);
	ae::log("Hello again. This is a string: ", "foo", "As you can see strings are not dumped.");
	
	$r = ae::request();
	
	switch ($r->segment(0, 'normal'))
	{
		case 'error':
			ae::output('examples/log/trigger_error.php');
			break;

		case 'exception':
			ae::output('examples/log/throw_exception.php', array(
				'foo' => 'bar',
				'bar' => 'foo'
			));
			break;
		
		case 'critical':
			ae::output('examples/log/shutdown_error.php', array(
				'foo' => 'bar',
				'bar' => 'foo'
			));
			break;
			
		default: ?>
	<p>Team bravo.</p>
	<iframe style="position: fixed; bottom: 20px; left: 20px; z-index: -1" src="/log/critical" frameborder="0" width="0" height="0"></iframe>
	<iframe style="position: fixed; bottom: 20px; left: 20px; z-index: -1" src="/log/exception" frameborder="0" width="0" height="0"></iframe>
	<script type="text/javascript" charset="utf-8">
		setTimeout(function() {
			var xhr = new XMLHttpRequest();
			xhr.open("GET", '/log/error', true);
			xhr.setRequestHeader('X-Requested-With', 'XMLHTTPRequest');
			xhr.send();
		}, 100);
	</script>
<?php
	}
?>