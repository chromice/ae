<?php

// Set default database paramters
$options = ae::options('database.default')
	->set('host', 'localhost')
	->set('user', 'root')
	->set('password', 'root')
	->set('database', 'ae');

echo '<h1>Official database example</h1>';

// New context
$example = new ae('examples.database');

// Import entity classes
ae::import('examples/database/authors.php');
ae::import('examples/database/books.php');

echo '<h2>Making queries</h2>';

// Install
Novels::install();
Authors::install();

// EXAMPLE: Insert
ae::database()
	->query("INSERT INTO {table} ({keys}) VALUES ({values})")
	->names(array(
		'table' => 'authors'
	))
	->values(array(
		'name' => 'Richar K. Morgan', // (sic)
		'nationality' => 'British'
	))
	->make();

$morgan_id = ae::database()->insert_id();

$transaction = ae::database()->transaction();

// EXAMPLE: Update
ae::database()
	->query("UPDATE {table} SET {keys=values} WHERE `id` = {author_id}")
	->names(array(
		'table' => 'authors'
	))
	->values(array(
		'name' => 'REPLACE(`name`, "Richar ", "Richard ")'
	), aeDatabase::statements) // don' escape
	->variables(array(
		'author_id' => $morgan_id
	), aeDatabase::values) // escape
	->make();

// Commit previous insert statement.
$transaction->commit();

// EXAMPLE: Shortcuts
ae::database()->update('authors', array(
	'nationality' => 'English'
), array('id' => $morgan_id));

// Rollback previous update
unset($transaction);

$stephenson_id = ae::database()->insert('authors', array(
	'name' => 'Neal Stephenson',
	'nationality' => 'American'
)); 
$gibson_id = ae::database()->insert('authors', array(
	'name' => 'William Ford Gibson',
	'nationality' => 'Canadian'
));


echo '<p>Seems to work pretty well so far&hellip;</p>';

echo '<h2>Retrieving data</h2>';

echo '<h3>Unordered list</h3>';
echo '<pre>';

// EXAMPLE: Unordered list
$count = ae::database()->count('authors');
$authors = ae::database()->select('authors')->result();

echo "There are $count authors in the database:\n";

while ($author = $authors->fetch())
{
	echo "- {$author['name']} ({$author['nationality']})\n";
}

echo '</pre>';

echo '<h3>Ordered list</h3>';
echo '<pre>';

// EXAMPLE: Ordered list
$authors = ae::database()
	->select('authors')
	->order_by('`name` ASC')
	->result() // return an instance of aeDatabaseResult
	->all(); // return an array of rows
$count = count($authors);

echo "There are $count authors in the result set:\n";

foreach ($authors as $author)
{
	echo "- {$author['name']}\n";
}

echo '</pre>';


echo '<h2>Active record</h2>';

echo '<h3>Finding record</h3>';
echo '<pre>';

// EXAMPLE: Finding record

// Create an instance of Authors pointed at Neal Stephenson 
// record in the "authors" table:
$stephenson = Authors::find($stephenson_id);

// Load the data
$stephenson->load(array('name', 'nationality'));

echo $stephenson->name; // Neal Stephenson
echo ' -- ';
echo $stephenson->nationality; // American

echo '</pre>';


// EXAMPLE: Updating a record

// Let's change William Gibson's nationality
$gibson = Authors::find($gibson_id);

$gibson->nationality = 'American';

// Update the record in the database
$gibson->save();


// EXAMPLE: Creating a record

$shaky = Authors::create(array(
	'name' => 'William Shakespeare',
	'nationality' => 'English'
));

// Create a new record in the database
$shaky->save();


echo '<h3>Retriving authors</h3>';
echo '<pre>';


// EXAMPLE: Retriving authors

$authors = ae::database()
	->query('SELECT * FROM `authors`')
	->many('Authors');
$count = $authors->count();

echo "There are $count authors in the database:\n";

while ($author = $authors->fetch())
{
	echo "- {$author->name}\n";
}

echo '</pre>';


// EXAMPLE: Deleting record

$shaky->delete();


// EXAMPLE: Populate content

$gibson->add_novel('Neuromancer');
$gibson->add_novel('Count Zero');
$gibson->add_novel('Mona Lisa Overdrive');

$stephenson->add_novel('Snow Crash');
$stephenson->add_novel('Cryptonomicon');
$stephenson->add_novel('Reamde');

// Note: we don't have to load author's record to add a novel.
$morgan = Authors::find($morgan_id);

$morgan->add_novel('Altered Carbon');
$morgan->add_novel('Broken Angels');
$morgan->add_novel('Woken Furies');


echo '<h3>Retriving novels</h3>';
echo '<pre>';

// EXAMPLE: Retriving all records

$novels = Novels::all();
$count = $novels->count();

echo "Here are all $count novels ordered alphabetically:\n";

while ($novel = $novels->fetch())
{
	echo "- {$novel->title} by {$novel->author->name}\n";
}

echo '</pre>';

// Uninstall
Novels::uninstall();
Authors::uninstall();

