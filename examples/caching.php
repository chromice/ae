<?php
	ae::options('response')->set('cache_dir', '/cache/');
	
	// Work for 50ms or so...
	$j = 0; while($j < 1000000) ++$j;
	
	$request = ae::request();
	$response = ae::response()
		->cache(5, $request->segment(0));

	if ($request->type() === 'json'):
		$response->type('json');
?>
{'hello':'world'}
<?php else: ?>
<h1>Hello World!</h1>
<?php endif ?>