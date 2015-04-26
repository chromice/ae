<?php

$response = ae::response('text');

echo "Hello World";

$response
    ->end()
    ->cache(\ae\ResponseCache::year)
    ->dispatch();
