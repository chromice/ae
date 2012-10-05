# æ — a simple PHP framework

æ |aʃ| solves many backend development problems in a simple and efficient way. It requires PHP version 5.3 or higher, and a recent version of MySQL and Apache with mod_rewrite.

- [File structure](#file-structure)
- [Core](#core)
	- [Contexts](#contexts)
	- [Views](#views)
	- [Libraries](#libraries)
	- [Buffer](#buffer)
	- [Switch](#switch)

## File structure

- `ae/` contains all framework code;
- `cache/` is used for storage of cached responses;
- `examples/` contains examples;
- `.htaccess` enables request handling and response caching;
- `index.php` initialises example application.

## Core

Before you can start using æ in your application, you must include `core.php` from the core directory:

```php
include 'ae/core.php';
```

æ does not import any other code or libraries untill you explicitly tell it so:

```php
// Import aeLog class from ae/log.php:
ae::import('log.php'); 
// Now you can use it:
aeLog::log('Log a message.', 'Or dump a varible:', $_SERVER);
```

### Contexts

Contexts let you keep the code of your application or modules in their own directories. By default æ will attempt to resolve relative file paths only against its core directory. You must manually create a new context, if you want it look for files in another directory first:

```php
$context = new ae('example', '/absolute/path/to/some/directory/');
```

In order to destroy the context manually, simply unset the variable:

```php
unset($context);
```

It is a good practice to declare the context of the module before the `ae::invoke()` statement without assigning it to a variable. This will declare a new context which will be immediately destroyed. You can recreate the context by its name whenever you actually need to use it:

```php

// Declare new context for the parent directory
new ae('module.example');

// Invoke this module as a singleton
ae::invoke('ModuleExample', ae::singleton);

class ModuleExample 
{
	function foo() 
	{
		$context = new ae('module.example');
		
		// Will load foo.php library in the module directory.
		return ae::load('foo.php');

		// $context is switched back to previous one at this point
	}
}
```

Many contexts can be created at the same time, but only the last one will be used. Once its destroyed æ will restore the previous one from the stack.

### Views

æ can return output of any script as a string:

```php
$output = æ::render('/your/page.php', array(
	'title' => 'Example!',
	'body' => '<h1>Hello world!</h1>'
));

// if content of /your/page.php is:
// <title><?= $title ?></title><body><?= $body ?></body>

echo $content;

// will produce:
// <title>Example!</title><body><h1>Hello world!</h1></body>
```

Or, to echo it straight away:

```php
æ::output('/your/page.php', array(
	'title' => 'Example!',
	'body' => '<h1>Hello world!</h1>'
));
```

### Libraries

You can use `ae::import()` method to include any PHP script:

```php
ae::import('path/to/library.php'); 
// æ will resolve the path and include the script, if it 
// has not been included yet.
```

In order to load a library you must use `ae::load()` method:

```php
$options = ae::load('options.php');
	
// Or you could write this: 
ae::import('options.php');
$options = new aeOptions();
```

You can configure library instance via second parameter of `ae::load()`. æ will pass its value to class constructor or object factory:

```php
$lib_options = ae::load('options.php', 'my_library_namespace');
	
// That is identical to:
ae::import('options.php');
$lib_options = new aeOptions('my_library_namespace');
```

æ does not "automagically" guess what class to use. You must use `ae::invoke()` method at the beginning of the loaded file to tell æ how and when you want it to create an object:

```php
ae::invoke('LibraryClassName');
// æ will create a new instance of LibraryClassName, every
// time the library is loaded.

ae::invoke('SingletonClassName', ae::singleton);
// Only one instance of SingletonClassName will be created;
// all subsequent calls to ae::load() will return that instance.

ae::invoke('a_factory_function', ae::factory);
// a_factory_function() function will be called.

ae::invoke(
	array('AnotherSingletonClassName', 'factory'), 
	ae::factory | ae:singleton
);
// AnotherSingletonClassName::factory() method will be 
// used to create and reuse a single instance of an object.
```

Please consult with the source code of the core libraries for real life examples.

### Buffer

`aeBuffer` is a utility class for capturing output in a thread–safe fasion:

```php
// Create a buffer and start capturing output:
$buffer = new aeBuffer();

echo 'Hellow world!';

// Now output the buffer:
$buffer->output();
// or you could:
echo $buffer->render();
```

All output is captured until you call `aeBuffer::output()` or `aeBuffer::render()` methods. If you do not use these methods, buffer's content will be discarded when its instance is destroyed, either manually or when execution leaves the scope:

```php
// No output is produced by this script:
$buffer = new aeBuffer(); 

echo 'Invisible text.';

unset($buffer);
```

Buffer can also be used as a template, e.g. when mixing HTML and PHP code:

```html
<?php $buffer = new aeBuffer() ?>
<p><a href="{url}">{name}</a> has been views {visits} times.</p>
<?php $buffer->output(array(
	'url' => $article->url,
	'name' => (strlen($article->name) > 20 ? substr($article->name, 0, 19) . '&hellip;' : $article->name),
	'visits' => number_format($article->visits)
)) ?>
```

### Switch

`aeSwitch` is a utility class that lets you switch the value of a variable to something else:

```php
echo $foo; // echoes 'foo'

$switch = new aeSwitch($foo, 'bar');

echo $foo; // echoes 'bar'

unset($switch);

echo $foo; // echoes 'foo' again
```

The switch will work even if an exception is thrown.

**TODO**: Document other libraries.




	

