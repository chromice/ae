<?php
	$container = ae::container('/examples/container/container_html.php');
	$container->set('title', 'æ');
	
	// Test default response headers
	$response = ae::response('html');
	
	// Test garbage collection
	aeResponseCache::collect_garbage();
?>
<h1>æ</h1>
<h2>Examples:</h2>
<p><?= ae::escape('This framework is <em>&ldquo;awesome&rdquo;</em>!') ?></p>
<ul>
<?php 
	foreach (array(
		'Caching' => 'caching/test',
		'Caching (html)' => 'caching/test.html',
		'Caching (json)' => 'caching/test.json',
		'Caching (direct)' => 'cache/caching/test/index.html/index.html',
		'Container' => 'container',
		'Database: Entity basics' => 'database_1',
		'Database: Full–on test' => 'database_2',
		'Form' => 'form',
		'Image' => 'image.jpeg',
		'Log' => 'log',
		'Log (trigger error)' => 'log/error',
		'Log (throw exception)' => 'log/exception',
		'Log (critical error)' => 'log/critical',
		'Options' => 'options',
		'Probes' => 'probe',
		'Request' => 'request/view/some/uri',
		'Session' => 'session'
	) as $name => $uri): $b = new aeBuffer(); 
?> 
	<li><a href="{uri}">{name}</a></li>
<?php
		$b->output(array(
			'uri' => '/' . trim($uri,'/'),
			'name' => $name
		));
	endforeach;
?>
</ul>
<?php 	$response->dispatch() ?>