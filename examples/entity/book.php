<?php

class Book extends aeDatabaseEntity
{
	protected static $database = 'default';
	protected static $table = 'books';
	protected static $accessor = array('id'); // or array('key_1', 'key_2')
	
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

$book = Book::create(array(
	'title' => 'Awesome'
))->save();

$books = Book::many();

while ($book = $books->fetch())
{
	echo $book->title . ' by ' . $book->author->name;
}

// Create new instance and point it to book #5
$book = Book::find(array(
	'id' => 5
));

// Load data
$book->load();

echo $book->title;

// Update data
$book->title = 'Bullshit';

// Save data
$book->save();

// Delete data
$book->delete();