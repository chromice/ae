# æ — a simple PHP framework

æ |aʃ| solves many backend development problems in a simple and efficient way. It requires PHP version 5.3 or higher, and a recent version of MySQL and Apache with mod_rewrite.

- [File structure](#file-structure)
- [Core](#core)
	- [Contexts](#contexts)
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
ae::import('log.php'); // imports aeLog class from ae/log.php, so you can:
aeLog::log('Log a message.', 'Or dump a varible:', $_SERVER);
```

### Contexts

Contexts let you keep the code of your application or modules in their own directories. By default æ will attempt to resolve relative file paths only against its core directory. You must manually create a new context, if you want it look for files in another directory first:

```php
$context = new ae('modules.example', '/absolute/path/to/some/directory/');
```

In order to destroy the context manually, simply unset the variable:

```php
unset($context);
```

**TODO**: Add examples of how contexts can be used in modules.


### Libraries

You can use `ae::import()` method to include any PHP script:

```php
ae::import('path/to/library.php'); // resolves the path and includes the script, if it has not been included yet.
```

In order to load a library you must use `ae::load()` method:

```php
$options = ae::load('options.php'); // creates an instance of aeOptions class.
	
// Or you could write this: 
ae::import('options.php');
$options = new aeOptions();
```

You can configure library instance via second parameter of `ae::load()`:

```php
$lib_options = ae::load('options.php', 'my_library_namespace');
	
// That is identical to:
ae::import('options.php');
$lib_options = new aeOptions('my_library_namespace');
```

æ does not "automagically" guess what class to use. You must use `ae::invoke()` method at the beginning of the loaded file to tell æ how and when you want it to create an object:

```php
ae::invoke('LibraryClassName');
// æ will create a new instance of LibraryClassName, every time the library is loaded.

ae::invoke('SingletonClassName', ae::singleton);
// Only one instance of SingletonClassName will be created; all subsequent calls to ae::load() will return that instance.

ae::invoke('a_factory_function`, ae::factory);
// a_factory_function() function will be used instead of a class constructor.

ae::invoke(array('AnotherSingletonClassName', 'factory'), ae::factory | ae:singleton);
// AnotherSingletonClassName::factory() method will be used to create and reuse a single instance of an object.
```

Please consult with the source code of the core libraries for real life examples.

### Buffer

`aeBuffer` is a utility class for capturing output in a thread–safe manner:

```php
$buffer = new aeBuffer(); 	// creates a buffer and start capturing output.

echo 'Hellow world!';

$buffer->output();
// or
echo $buffer->render();
// echos the captured output.
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




	

