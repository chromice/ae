<?php
	// ae::import('log.php');
	ae::options('response')->set('directory', '/cache/');
	
	// Work for 50ms or so...
	$j = 0; while($j < 1000000) ++$j;
	
	$request = ae::request();
	$response = ae::response($request->type() === 'json' ? 'json' : 'html');
	
	// aeResponse::delete('caching/test');

if ($request->type() === 'json'):
?>
{'hello':'world'}
<?php else: ?>
<h1>Hello World!</h1>
<?php endif ?>
<?php

	$response->cache(5, false)
		->save('caching/test.' . ($request->type() === 'json' ? 'json' : 'html'))
		->dispatch();
