<?php

ae::import('ae/database.php');
ae::import('examples/database/authors.php');

class Novels extends aeDatabaseTable
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
				`content` text NOT NULL,
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
			->join('{authors} ON {authors}.`id` = {table:primary}.`author_id`')
			->where('{table:primary}.`id` = {book_id}', array(
				'book_id' => $id
			))
			->aliases(array(
				'authors' => Authors::name()
			))
			->using('Authors', 'author')
			->one('Novels');
	}
	
	public static function many($limit, $offset = null)
	{
		return static::database()
			->select(self::name())
			->join('{authors} ON {authors}.`id` = {table:primary}.`author_id`')
			->limit($limit, $offset)
			->aliases(array(
				'authors' => Authors::name()
			))
			->using('Authors', 'author')
			->many('Novels');
	}
	
	public static function all()
	{
		return static::database()
			->select(self::name())
			->join('{authors} ON {authors}.`id` = {table:primary}.`author_id`')
			->order_by('{table:primary}.`title`')
			->aliases(array(
				'authors' => Authors::name()
			))
			->using('Authors', 'author')
			->many('Novels');
	}
}