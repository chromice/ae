<?php
	
	use \ae\Core as ae;
	
	// ae::import('log.php');
	ae::options('ae.response')
		->set('compress_output', true);
	ae::options('ae.response.cache')
		->set('directory_path', '/cache');
	
	// Work for a few seconds...
	$j = 0; while($j < 10000000) ++$j;
	
	$request = ae::request();
	$type = $request::type() === 'json' ? 'json' : 'html';
	$response = ae::response($type);
	
	// ResponseCache::delete('caching/test');

?>
<?php if ($type === 'json'): ?>
{'hello':'world'}
<?php else: ?>
<h1>Hello World!</h1>
<?php endif ?>
<?php
	$response
		->cache(5, $request::uri())
		->dispatch();
