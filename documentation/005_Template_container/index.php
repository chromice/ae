<?php 

$container = ae::container(__DIR__ . '/container.php', [
    'title' => 'Container example'
]);

$container['alert'] = 'h1';

?>
<h1>Hello World!</h1>
<?php

// Optional
$container->end();

?>
