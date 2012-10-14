<?php
	// ae::import('log.php');
	ae::options('response')
		->set('directory', '/cache/')
		->set('compress', true);
	
	// Work for 1 seconds or so...
	$j = 0; while($j < 10000000) ++$j;
	
	$request = ae::request();
	$type = $request->type() === 'json' ? 'json' : 'html';
	$response = ae::response($type);
	
	// aeResponse::delete('caching/test');

if ($request->type() === 'json'):
?>
{'hello':'world'}
<?php else: ?>
<h1>Hello World!</h1>
<?php endif ?>
<?php

	$response
		->cache(5)
		->save('caching/test.' . $type)
		->dispatch();
