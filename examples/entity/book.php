<?php

class Book extends aeDatabaseEntity
{
	protected $database = 'default';
	protected $table = 'books';
	protected $primary = 'id'; // or array('key_1', 'key_2')
	
	public static function one($id)
	{
		return static::database()->query("SELECT * 
			FROM {books} 
			JOIN {authors} ON {authors}.`id` = {books}.`author_id` 
			WHERE {books}.`id` = {book_id}")
			->variables(array(
				'book_id' => $id
			))
			->names(array(
				'books' => static::table(),
				'authors' => Author::table()
			))
			->using('Author', 'author')
			->one('Book');
	}
	
	public static function many($limit = null, $offset = null)
	{
		$query = "SELECT * 
			FROM {books} 
			JOIN {authors} ON {authors}.`id` = {books}.`author_id`";
		
		if (!is_null($limit))
		{
			$query = ' LIMIT {limit}';
			
			if (!is_null($offset))
			{
				$query = ' OFFSET {offset}';
			}
		}
		
		return static::database()->query($query)
			->variables(array(
				'book_id' => $id,
				'limit' => $limit,
				'offset' => $offset
			))
			->names(array(
				'books' => static::table(),
				'authors' => Author::table()
			))
			->using('Author', 'author')
			->many('Book');
	}
}

$books = Book::many();

while($book = $books->fetch())
{
	echo $book['title'] . ' by ' . $book['author']['name'];
}