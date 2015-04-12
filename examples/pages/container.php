<?php
	$container = ae::container('/examples/container/container_inner.php')
		->set('title', 'Example: Container')
		->set('header', 'Hello World!');
?>
<p>A simple nestable view heirarchy. Let&rsquo;s count:</p>
<ul>
<?php 
	for ($i=1; $i <= 5; $i++):
		$b = new \ae\Buffer(); 
?>
	<li>{number}</li>
<?php
		$b->output([
			'number' => $i
		]); 
	endfor
?>
</ul>
