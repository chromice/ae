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
ae::import('author.php');
ae::import('book.php');

// Install
Books::install();
Authors::install();


// Create a new entity and save it to database
$author = Authors::create(array(
	'name' => 'Jerome David Salinger'
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

// Save data
$_book->save();

// Reload original instance
$book->load();

echo $book->title . '<br>';

// Delete data
$book->delete();

// Install
Books::uninstall();
Authors::uninstall();


