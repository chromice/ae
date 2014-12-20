<?php
///include 'ae/core.php';
use \ae\Core as ae;

echo 'Hello ' . ae::request()->segment(0, "world") . '!';

?>