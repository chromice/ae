<?php

// Set default database paramters
$options = ae::options('database.default')
	->set('host', 'localhost')
	->set('user', 'root')
	->set('password', 'root')
	->set('database', 'ae_database');

// New context
$example = new ae('examples.database', dirname(__FILE__));

// Import tables
ae::import('authors.php');
ae::import('books.php');

// Install
Books::install();
Authors::install();


// Create a new entity and save it to database
$author = Authors::create(array(
	'name' => 'Jerome David Salinger',
	'non_existant' => 'Property'
))->save();

$book = Books::create(array(
	'author_id' => $author->id,
	'title' => 'The Catcher in the Dryer',
	'content' => '...',
))->save();

$books = Books::many();

while ($b = $books->fetch())
{
	echo $b->title . ' by ' . $b->author->name . '<br>';
}

// Create new instance of that book
$_book = Books::find(array(
	'id' => $book->id
));


// Load data
$_book->load();

echo $_book->title . '<br>';

// Update data
$_book->title = 'The Catcher in the Rye';
$_book->transient = 'Transient property';
$_book->transient2 = 'works correctly';

// Save data
$_book->save();

// Reload original instance
$book->load();

echo $book->title . '<br>';

echo $_book->transient . ' ' . $_book->transient2 . '<br>';

// Delete data
$book->delete();

// Install
Books::uninstall();
Authors::uninstall();


