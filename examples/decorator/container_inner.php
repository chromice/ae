<?php
	$container = ae::load('container.php','/examples/decorator/container_html.php');
	$container->set('title', 'Example: Decorator');
?>
<h1><?= $header ?></h1>
<?= $content ?>
