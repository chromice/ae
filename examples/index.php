<?php
	$container = ae::container('/examples/container/container_html.php');
	$container->set('title', 'æ');
	
	$Request = ae::request();
?>
<h1>æ</h1>
<h2>Examples:</h2>
<ul>
<?php foreach (array(
		'Caching' => 'caching/test',
		'Caching (html)' => 'caching/test.html',
		'Caching (json)' => 'caching/test.json',
		'Caching (direct)' => 'cache/caching/test/index.html/index.html',
		'Container' => 'container',
		'Database' => 'database/example',
		'Log' => 'log',
		'Log (trigger error)' => 'log/error',
		'Log (throw exception)' => 'log/exception',
		'Log (critical error)' => 'log/critical',
		'Options' => 'options',
		'Probes' => 'probe',
		'Request' => 'request/view/some/uri'
	) as $name => $uri): ?>
	<li><a href="/<?= trim($uri,'/') ?>"><?= $name ?></a></li>
<?php endforeach ?>
</ul>

