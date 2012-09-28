<?php
	$container = ae::container('/examples/container/container_html.php')
		->set('title', $title);
?>
<h1><?= $header ?></h1>
<?= $content ?>
