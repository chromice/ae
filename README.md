# æ — a simple PHP framework

## Introduction

æ |aʃ| is a PHP framework written with ease of maintenance, memory footprint and performance in mind to solve the most common and basic web problems: importing and extending code, changing configuration, processing requests, preparing responses  and caching them, reading and writing to the database, etc.

æ requires PHP of at least version 5.3, and a recent version of MySQL and Apache with mod_rewrite.

## File structure

- `.htaccess` enables request handling and response caching;
- `ae/` contains all framework code;
- `cache/` is used for storing cached responses;
- `examples/` contains examples;
- `index.php` initialises example application.

## Core

In order to begin using æ in your application you have to include `core.php` from the core directory:

	include 'ae/core.php';

æ does not import any other code or libraries untill you explicitly tell it so:

	ae::import('log.php'); // imports aeLog class from ae/log.php, so you can:
	aeLog::log('Log a message.', 'Or dump a varible:', $_SERVER);

### Contexts

Out of the box æ will attempt to resolve relative file paths only against its core directory. You must manually create a new context for any other directory:

	$context = new ae('modules.example', '/absolute/path/to/some/directory/');

As long as `$context` exists, æ will look for files in that directory first. Only if it finds nothing there, will it fall back to its core directory.

In order to destroy the context manually, simply unset the variable:

	unset($context);

Contexts let you keep the code of your application or modules in their own directories and load scripts without worrying in what directory they are.

### Libraries

You can use `ae::import()` method to include any PHP script:

	ae::import('path/to/library.php'); 
	// resolves the path and includes the script,
	// if it has not been included yet.

In order to load a library you must use `ae::load()` method:

	$options = ae::load('options.php'); 
	// creates an instance of aeOptions class
	
	// Or you could write this: 
	ae::import('options.php');
	$options = new aeOptions();

You can configure library instance by passing using second parameter of `ae::load()`:

	$lib_options = ae::load('options.php', 'my_library_namespace');
	
	// which does the same thing as:
	ae::import('options.php');
	$lib_options = new aeOptions('my_library_namespace');

æ does not "automagically" guess what class to use. You must use `ae::invoke` method at the beginning of the loaded file to tell æ how and when you want it to create an object:

	ae::invoke('LibraryClassName');
	// æ will create a new instance of LibraryClassName, every
	// time the library is loaded.

	ae::invoke('SingletonClassName', ae::singleton);
	// Only one instance of SingletonClassName will be created;
	// all subsequent calls to ae::load() will return 
	// that instance.

	ae::invoke('a_factory_function`, ae::factory);
	// a_factory_function() will be used instead of a class constructor.

	ae::invoke(array('AnotherSingletonClassName', 'factory'), ae::factory | ae:singleton);
	// AnotherSingletonClassName::factory() method will be used to
	// create and reuse a single instance of an object.

Please consult with the source code of the core libraries for real life examples.

### Buffer

`aeBuffer` is a utility class for capturing output in a thread–safe manner:

	$buffer = new aeBuffer(); 
	// creates a buffer and start capturing output.

	echo 'Hellow world!';

	$buffer->output();
	// or
	echo $buffer->render();
	// echos the captured output.

All output is captured until you call `aeBuffer::output()` or `aeBuffer::render()` methods. If you do not use these methods, buffer's content will be discarded when its instance is destroyed, either manually or when execution leaves the scope:

	// No output is produced by this script
	$buffer = new aeBuffer(); 

	echo 'Invisible text.';

	unset($buffer);

Buffer can also be used as a template, e.g. when mixing HTML and PHP code:

	<?php $buffer = aeBuffer() ?>
	<p><a href="{url}">{name}</a> has been views {visits} times.</p>
	<?php $buffer->output(array(
		'url' => $article->url,
		'name' => (strlen > 20 ? substr($article->name, 0, 19) $article->name),
		'views' => number_format($article->visits)
	))?>
	

### Switch

`aeBuffer` is a utility class that allows you change the value of a variable to something else for as long as the switch instance exists:
	
	echo $foo; // echoes 'foo'
	$switch = aeSwitch($foo, 'bar');
	echo $foo; // echoes 'bar'
	unset($switch);
	echo $foo; // echoes 'foo' again

The switch will change back the value of a variable, even if an exception is thrown.

*TODO*: Document core libraries.


	

