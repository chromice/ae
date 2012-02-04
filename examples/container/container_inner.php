<?php
	$container = ae::load('container.php','/examples/container/container_html.php');
	$container->set('title', 'Example: Decorator');
?>
<h1><?= $header ?></h1>
<?= $content ?>
