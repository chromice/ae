<?php

$queries = ae::database()
    ->select('*')
    ->from('table')
    ->order_by('column ASC')
    ->make();
