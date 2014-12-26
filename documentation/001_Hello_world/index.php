<?php

/// include 'path/to/ae/loader.php';
/// ---

// This part will be cut out of the example.

/// +++

echo 'Hello ' . ae::request()->segment(0, "world") . '!';

/// ---
// Seriously

// I can even execute code here, and it won't make a difference...

$i = 2 + 3;

// ...unless it is echoed.

/// +++

?>