<?php
	$decorator = ae::load('decorator.php','/examples/decorator/decorator.php');
	$decorator->set('title', 'æ');
	
	$Request = ae::load('request.php');
?>
<h1>æ</h1>
<h2>Examples:</h2>
<ul>
<?php foreach (array(
		'Decorator' => 'decorator',
		'Options' => 'options',
		'Probes' => 'probes',
		'Request' => 'request/is/here'
	) as $name => $uri): ?>
	<li><a href="/?uri=<?= $Request->href($uri) ?>"><?= $name ?></a></li>
<?php endforeach ?>
</ul>

