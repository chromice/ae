<?php
	$container = ae::container('/examples/container/container_html.php');
?>
<h1><?= $header ?></h1>
<?= $content ?>
<?= ae::render('/examples/container/aside.php') ?>
