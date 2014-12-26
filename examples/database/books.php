<?php

ae::import('ae/database.php');
ae::import('examples/database/authors.php');

class Novels extends \ae\DatabaseTable
{
	public static function name()
	{
		return 'books'; // that is the real name of the table
	}
	
	public static function install()
	{
		static::database()->query("CREATE TABLE IF NOT EXISTS {books} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`author_id` int(10) unsigned NOT NULL,
				`title` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8")
			->aliases(array(
				'books' => static::name()
			))
			->make();
	}

	public static function uninstall()
	{
		static::database()->query("DROP TABLE IF EXISTS {books}")
			->aliases(array(
				'books' => static::name()
			))
			->make();
	}
	
	public static function one($id)
	{
		return static::database()
			->select(self::name())
			->joining('Authors', 'author')
			->where('{table}.`id` = {book_id}', array(
				'book_id' => $id
			))
			->one('Novels');
	}
	
	public static function many($limit, $offset = null)
	{
		return static::database()
			->select(self::name())
			->joining('Authors', 'author')
			->limit($limit, $offset)
			->many('Novels');
	}
	
	public static function all()
	{
		return static::database()
			->select(self::name())
			->joining('Authors', 'author')
			->order_by('{table}.`title`')
			->many('Novels');
	}
}