<?php 

use \ae\Core as ae;

$container = ae::container(__DIR__ . '/container.php')
    ->set('title', 'Container example');

?>
<h1>Hello World!</h1>
