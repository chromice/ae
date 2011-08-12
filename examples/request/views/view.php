<?php
	$request = ae::load('request.php');
?>
Hello world! Uri: <code><?= $request->segment(0); ?> <?= $request->segment(1); ?></code>