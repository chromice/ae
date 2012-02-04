<?php
	$container = ae::load('container.php','/examples/container/container_html.php');
	$container->set('title', 'æ');
	
	$Request = ae::load('request.php');
?>
<h1>æ</h1>
<h2>Examples:</h2>
<ul>
<?php foreach (array(
		'Database' => 'database',
		'Container' => 'container',
		'Options' => 'options',
		'Probes' => 'probe',
		'Request' => 'request/view/some/uri'
	) as $name => $uri): ?>
	<li><a href="/<?= trim($uri,'/') ?>"><?= $name ?></a></li>
<?php endforeach ?>
</ul>

