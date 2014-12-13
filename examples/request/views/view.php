<?php
	$request = \ae\Core::request();
?>
Hello world! URI: <code><?= $request->segment(0); ?> <?= $request->segment(1); ?></code> <br>