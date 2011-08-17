<?php

/*
	Quick and easy reads and writes.
*/
$options = ae::load('options.php','database');
$options->set('default', 'mysql://root:root@localhost/my_database');
/*
	Or use an array:
	
	array(
		'type' => 'mysql',
		'host' => 'localhost',
		'user' => 'root',
		'pass' => 'root',
		'database' => 'my_database'
	);
*/

$db = ae::load('database.php');
// or
// $db = ae::load('database.php', 'default');
// or
// $db = ae::load('database.php', 'mysql://root:root@localhost/my_database');

/*
	Simple queries can be made with one function call
*/

$db->do('DELETE FROM `a_table` WHERE `id` = {0}', 123);


/*
	Templates are good complex queries.
*/

$result = $db->query('SELECT {fields} FROM {table} AS {alias}
		{db:join}
		WHERE {alias}.id = {id} AND {alias}.status = {status}
		{db:order-by}
		{db:limit}')
	// ->syntax('{','}')
	->prefix('{alias}', false) // false = do not gather into a collection
	->list('{fields}', 'id', 'name', 'status')
	->prefix('{related-table}','related')
	->list('{fields}', 'id', 'name')
	->join('{related-table}','{table}.remote_id = {related-table}.id');
	->protect(array(
		'table' => 'some-table',
		'alias' => 'an-alias',
		'related-table' => 'another-table'
	))
	->escape(array(
		'id' => 1,
		'status' => 'online'
	))
	// ->sql(); to return an SQL string
	->as('ObjectModelClass')
	->as('RelatedObjectModelClass','related')
	->do();
	// or

// Result implements Iterator interface (http://www.php.net/manual/en/class.iterator.php)
foreach ($result as $object)
{
	echo $object['id'];
	echo $object['name'];
	echo $object['status'];
	
	// "related" collection
	echo $object['related']['id'];
	echo $object['related']['name'];
	
	// The following is valid for "one to one" or "many to one" relationship
	if ($object['status'] !== 'ready')
	{
		$object['related']->delete();
		continue;
	}
	
	$object['related']['status'] = $object['status'];
	$object['related']->save(); // implemented by RelatedObjectModelClass
}

$db->query('UPDATE {table} SET {db:set} WHERE id = {id}')
// $db->query('INSERT INTO {table} ({db:keys}) VALUES ({db:values})')
	->protect('table','some-table')
	->escape('id',1)
	->set('key','value')
	->set($an_array)
	->do();


