<?php
	ae::utilize('inspector')
		// ->set('directory_path', '/logs')
		->set('dump_context', true);
	
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
		default:
			ae::log('Let\'s dump $_ENV:', $_ENV);
			
			trigger_error("This is a notice.", E_USER_NOTICE);
			trigger_error("This is a warning.", E_USER_WARNING);
			
			$c = ae::container('/examples/container/container_html.php')
				->set('title', 'Example: Log inspector');
			
			ae::log("Hello kitty. This is a number: ", 24, "And this is a boolean: ", true, ' And a bit of void: ', NULL);
			ae::log("Hello again. This is a string: ", "foo", "As you can see, strings are not dumped.");
?>
	<p>You should see an inspector button in the bottom left corner. You ip address is: <?= $r::ip_address() ?>. It should be in whitelist, unless it's 127.0.0.1</p>
	<iframe style="width: 50%;float:left;height:400px;" src="/log/critical" frameborder="0"></iframe>
	<iframe style="width: 50%;float:right;height:400px;" src="/log/exception" frameborder="0"></iframe>
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