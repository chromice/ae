<?php

$response = ae::response('text');

echo "Hello World";

$response
    ->cache(\ae\ResponseCache::year)
    ->dispatch();
