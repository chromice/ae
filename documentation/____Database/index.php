<?php

ae::options('ae::database(default)')
	->set('host', 'localhost')
	->set('user', 'root')
	->set('password', 'root')
	->set('database', 'ae');

?>

# Official database example

<?php

// Import entity classes
ae::import(__DIR__ . '/authors.php');
ae::import(__DIR__ . '/books.php');

?>

## Making queries

<?php

// Install
Novels::install();
Authors::install();

// EXAMPLE: Insert
ae::database()
	->query("INSERT INTO {table} ({data:names}) VALUES ({data:values})")
	->aliases([
		'table' => 'authors'
	])
	->data([
		'name' => 'Richar K. Morgan', // (sic)
		'nationality' => 'British'
	])
	->make();

$morgan_id = ae::database()->insert_id();
// Open transaction
$transaction = ae::database()->transaction();

// EXAMPLE: Update
ae::database()
	->query("UPDATE {table} SET {data:set} WHERE `id` = {author_id}")
	->aliases([
		'table' => 'authors'
	])
	->data([
		'name' => 'REPLACE(`name`, "Richar ", "Richard ")'
	], \ae\Database::statement) // don' escape
	->variables([
		'author_id' => $morgan_id
	], \ae\Database::value) // escape
	->make();

// ...perform a series of queries...
$transaction->commit();
// ...perform another series of queries...
// EXAMPLE: Shortcuts
ae::database()->update('authors', [
	'nationality' => 'English'
], ['id' => $morgan_id]);
/// $transaction->commit();
// ...close transaction and roll back uncommitted queries.
unset($transaction);

$stephenson_id = ae::database()->insert('authors', [
	'name' => 'Neal Stephenson',
	'nationality' => 'American'
]); 
$gibson_id = ae::database()->insert('authors', [
	'name' => 'William Ford Gibson',
	'nationality' => 'Canadian'
]);


?>

Seems to work pretty well so far...

<?php

?>

## Retrieving data

<?php

?>

### Unordered list

<?php

// EXAMPLE: Unordered list
$count = ae::database()->count('authors');
$authors = ae::database()->select('authors')->result();

echo "There are $count authors in the database:\n";

while ($author = $authors->fetch())
{
	echo "- {$author['name']} ({$author['nationality']})\n";
}

?>

### Ordered list

<?php

// EXAMPLE: Ordered list
$authors = ae::database()
	->select('authors')
	->order_by('`name` ASC')
	->result() // return an instance of DatabaseResult
	->all(); // return an array of rows
$count = count($authors);

echo "There are $count authors in the result set:\n";

foreach ($authors as $author)
{
	echo "- {$author['name']}\n";
}

?>

## Active record

### Finding record

<?php

// EXAMPLE: Finding record

// Create an instance of Authors pointed at Neal Stephenson 
// record in the "authors" table:
$stephenson = Authors::find($stephenson_id);

// Load the data
$stephenson->load(['name', 'nationality']);

echo $stephenson->name; // Neal Stephenson
echo ' -- ';
echo $stephenson->nationality; // American


// EXAMPLE: Updating a record

// Let's change William Gibson's nationality
$gibson = Authors::find($gibson_id);

$gibson->nationality = 'American';

// Update the record in the database
$gibson->save();


// EXAMPLE: Creating a record

$shaky = Authors::create([
	'name' => 'William Shakespeare',
	'nationality' => 'English'
]);

// Save the new record to the database
$shaky->save();


?>


### Retriving authors

<?php

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


?>

### Retriving novels

<?php

// EXAMPLE: Retriving all records

$novels = Novels::all();
$count = $novels->count();

echo "Here are all $count novels ordered alphabetically:\n";

while ($novel = $novels->fetch())
{
	$clone = clone $novel;
	$clone->author->name = 'Blah blah blah';
	
	echo "- {$novel->title} by {$novel->author->name}\n";
}


// Uninstall
Novels::uninstall();
Authors::uninstall();

