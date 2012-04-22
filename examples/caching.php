<?php
	ae::options('response')->set('cache_dir', '/cache/');
	ae::response()->cache(5, true);
	
	// Work for 50ms or so...
	$j = 0; while($j < 1000000) ++$j;
	
	$response = ae::response();
	$request = ae::request();

	if ($request->type() === 'json'):
		$response->type('json');
?>
{'hello':'world'}
<?php else: ?>
<h1>hello Fuckers</h1>
<?php endif ?>