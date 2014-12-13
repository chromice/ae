<?php
	$container = \ae\Core::container('/examples/container/container_aside.php')
		->set('title', 'Aside');
?>
<p>This is some content aside</p>