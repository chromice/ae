<?php

ae::import('database.php');

class Authors extends aeDatabaseTable
{
	public static function install()
	{
		static::database()->query("CREATE TABLE IF NOT EXISTS {authors} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8")
			->names(array(
				'authors' => static::name()
			))
			->run();
	}

	public static function uninstall()
	{
		static::database()->query("DROP TABLE IF EXISTS {authors}")
			->names(array(
				'authors' => static::name()
			))
			->run();
	}	
}