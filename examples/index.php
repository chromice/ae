<?php
	$container = ae::container('/examples/container/container_html.php');
	$container->set('title', 'æ');
	
	$Request = ae::request();
?>
<h1>æ</h1>
<h2>Examples:</h2>
<ul>
<?php foreach (array(
		'Container' => 'container',
		'Database' => 'database/example',
		'Options' => 'options',
		'Probes' => 'probe',
		'Request' => 'request/view/some/uri'
	) as $name => $uri): ?>
	<li><a href="/<?= trim($uri,'/') ?>"><?= $name ?></a></li>
<?php endforeach ?>
</ul>

