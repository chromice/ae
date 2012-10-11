<?php

ae::import('ae/database.php');
ae::import('authors.php');

class Books extends aeDatabaseTable
{	
	public static function install()
	{
		static::database()->query("CREATE TABLE IF NOT EXISTS {books} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`author_id` int(10) unsigned NOT NULL,
				`title` varchar(255) NOT NULL,
				`content` text NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8")
			->names(array(
				'books' => static::name()
			))
			->run();
	}

	public static function uninstall()
	{
		static::database()->query("DROP TABLE IF EXISTS {books}")
			->names(array(
				'books' => static::name()
			))
			->run();
	}
	
	public static function one($id)
	{
		return static::database()
			->query("SELECT * 
				FROM {books} 
				JOIN {authors} ON {authors}.`id` = {books}.`author_id` 
				WHERE {books}.`id` = {book_id}")
			->variables(array(
				'book_id' => $id
			))
			->names(array(
				'books' => static::name(),
				'authors' => Authors::name()
			))
			->using('Authors', 'author')
			->one('Books');
	}
	
	public static function many($limit, $offset = null)
	{
		return static::database()
			->query('SELECT * 
				FROM {books} 
				JOIN {authors} ON {authors}.`id` = {books}.`author_id`
				{sql:limit}')
			->limit($limit, $offset)
			->names(array(
				'books' => static::name(),
				'authors' => Authors::name()
			))
			->using('Authors', 'author')
			->many('Books');
	}
}