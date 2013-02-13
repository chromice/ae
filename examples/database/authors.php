<?php

ae::import('ae/database.php');

class Authors extends aeDatabaseTable
{
	public function add_novel($title)
	{
		$ids = $this->ids();
		
		return Novels::create(array(
				'author_id' => $ids['id'],
				'title' => $title
			))->save();
	}
	
	public static function install()
	{
		static::database()->query("CREATE TABLE IF NOT EXISTS {authors} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				`nationality` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8")
			->names(array(
				'authors' => static::name()
			))
			->make();
	}

	public static function uninstall()
	{
		static::database()->query("DROP TABLE IF EXISTS {authors}")
			->names(array(
				'authors' => static::name()
			))
			->make();
	}	
}