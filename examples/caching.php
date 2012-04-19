<?php
	
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