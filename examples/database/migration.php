<?php 

$table = "some_new_table";

$initialise = ae::database()
	->table($table)
	->create("
		CREATE TABLE {table} (
		  `id` int(10) NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `private_memgroup` tinyint(4) default '0' COMMENT 'determine whether the document group is private to manager users',
		  `private_webgroup` tinyint(4) default '0' COMMENT 'determines whether the document is private to web users',
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `name` (`name`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Contains data used for access permissions.';
	");

$remove_data_rows = ae::database()
	->state($initialise)
	->apply("
		UPDATE TABLE {table} DROP COLUMN `private_memgroup`;
	");

$table_no_more = ae::database()
	->table($table) // or ->state($add_more_data_rows)
	->drop();
