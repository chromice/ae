<?php

// Configure the "default" database connection
ae::options('ae.database.default')
	->set('host', 'localhost')
	->set('user', 'root')
	->set('password', 'root')
	->set('database', 'ae');

ae::options('ae.database')
	->set('log', true);

try {
	$db = ae::database(); // same as ae::database("default");
	
	$db->query("SELECT 1")->make();
} catch (\ae\DatabaseException $e) {
	echo 'Something went wrong: ' . $e->getMessage();
}

