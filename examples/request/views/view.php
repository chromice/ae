<?php
	$request = ae::request();
?>
Hello world! Uri: <code><?= $request->segment(0); ?> <?= $request->segment(1); ?></code> <br>