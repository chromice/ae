<?php 

$initialise = ae::database()
	->initialise("some_new_table", 
		"CREATE TABLE {table} (
		  `id` int(10) NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `private_memgroup` tinyint(4) default '0' COMMENT 'determine whether the document group is private to manager users',
		  `private_webgroup` tinyint(4) default '0' COMMENT 'determines whether the document is private to web users',
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `name` (`name`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Contains data used for access permissions.'")
	->commit();

$remove_data_rows = $initialise
	->update('UPDATE TABLE {table} DROP COLUMN `private_memgroup`')
	->update('UPDATE TABLE {table} DROP COLUMN `private_webgroup`')
	->commit();

$table_no_more = $remove_data_rows
	->update('DROP TABLE {table}')
	->commit();
