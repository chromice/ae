<?php

$query = ae::database()
    ->select('*')
    ->from('table')
    ->order_by('column ASC');
    
$result = $query->make();
