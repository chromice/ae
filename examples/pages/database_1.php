<?php

// Turn on query logging
ae::options('ae.database')
	->set('log', true);

// Set default database paramters
ae::options('ae.database.default')
	->set('host', 'localhost')
	->set('user', 'root')
	->set('password', 'root')
	->set('database', 'ae');

// When socket cannot be detected automatically...
// ae::options('ae.database.default')->set('socket', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

// New context
$example = new ae('examples.database');

// Import entity classes
ae::import('examples/database/authors.php');
ae::import('examples/database/books.php');

// Install
Novels::install();
Authors::install();

// Create new entities and save them to database
$author = Authors::create(array(
	'name' => 'Jerome David Salinger',
	'nationality' => 'American',
	'non_existant' => 'Property'
))->save();

$author->nationality = 'United States of America';
$author->save();

$book = Novels::create(array(
	'author_id' => $author->id,
	'title' => 'The Catcher in the Dryer',
	'content' => '...',
))->save();

// Retrieve all books
$books = Novels::many(5);

while ($b = $books->fetch())
{
	echo 'Original: ' . $b->title . ' by ' . $b->author->name . '<br>';
}

// Create new instance of that book
$_book = Novels::find($book->id);


// Load data
$_book->load();

// Custom query that does the same thing
$__book = Novels::one($book->id);

echo 'Copy 1: ' . $_book->title . '<br>';
echo 'Copy 2: ' . $__book->title . '<br>';

// Update data
$_book->title = 'The Catcher in the Rye';
$_book->transient = 'Transient property';
$_book->transient2 = 'works correctly';

// Save data
$_book->save();

// Reload original instance
$book->load();

echo 'Updated: ' . $book->title . '<br>';

echo $_book->transient . ' ' . $_book->transient2 . '<br>';

echo 'Number of books: ' . ae::database()->count('books');

// Delete data
$book->delete();

// Uninstall
Novels::uninstall();
Authors::uninstall();


