<?php
	$container = ae::load('container.php','/examples/decorator/container_html.php');
	$container->set('title', 'æ');
	
	$Request = ae::load('request.php');
?>
<h1>æ</h1>
<h2>Examples:</h2>
<ul>
<?php foreach (array(
		'Decorator' => 'decorator',
		'Options' => 'options',
		'Probes' => 'probe',
		'Request' => 'request/some/uri/segments'
	) as $name => $uri): ?>
	<li><a href="/<?= trim($uri,'/') ?>"><?= $name ?></a></li>
<?php endforeach ?>
</ul>

