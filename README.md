# æ — a simple PHP framework

æ |aʃ| solves many backend development problems in a simple and efficient way. It requires PHP version 5.3 or higher, and a recent version of MySQL and Apache with mod_rewrite.

- [Getting started](#getting-started)
	- [Importing code](#importing-code)
	- [Running code](#running-code)
	- [Loading libraries](#loading-libraries)
- [Stock libraries](#stock-libraries)
	- [Buffer](#buffer)
	- [Container](#container)
	- [Options](#options)
	- [Switch](#switch)
- [Licence](#licence)

- - - - -

## Getting started

In order to start using æ in your application, you must include **core.php** located in the **ae** directory:

```php
include 'ae/core.php';
```

æ does not import any other code or libraries untill you explicitly tell it to do so:

```php
// Load options object for my_app namespace:
$options = ae::load('ae/options.php', 'my_app');

// Now you can use it:
$options->set('foo', 'bar');
$options->get('foo');
```

The sole purpose of `ae` class is to enable you to import code, run scripts and capture their output, and load stock or custom libraries. All file methods accepts both absolute and relative file paths. By default non–absolute paths must be relative to the directory that contains **ae** directory.

If you want æ to look for a file in another directory, you can register a new context for it:

```php
ae::register('example', '/absolute/path/to/some/directory/');
```

Now you can create that context, whenever you want æ to look for files in that directory:

```php
$context = new ae('example');

echo ae::resolve('foo.php');
// will echo "/absolute/path/to/some/directory/foo.php", if the file exists.
```

In order to destroy the context manually, simply unset the variable:

```php
unset($context);
```

Many contexts can exist at the same time, but only the lastest one will be used. Once its destroyed æ will restore the previously active context. It is a generally a good idea to assign contexts to local variables and destroy them as soon as possible.


### Importing code

You can use `ae::import()` method to include any PHP script:

```php
ae::import('path/to/library.php'); 
// æ will resolve the path and include the script, if it 
// has not been included yet.
```

### Running code

`ae::render()` can return output of any script as a string:

```php
$output = ae::render('/your/page.php', array(
	'title' => 'Example!',
	'body' => '<h1>Hello world!</h1>'
));

// if content of /your/page.php is:
// <title><?= $title ?></title><body><?= $body ?></body>

echo $output;

// will produce:
// <title>Example!</title><body><h1>Hello world!</h1></body>
```

Or you can use `ae::output()` to output the rendered content immediately:

```php
ae::output('/your/page.php', array(
	'title' => 'Example!',
	'body' => '<h1>Hello world!</h1>'
));
```

### Loading libraries

In order to load a library you must use `ae::load()` method:

```php
$options = ae::load('ae/options.php');
	
// Or you could write this: 
ae::import('ae/options.php');
$options = new aeOptions();
```

You can configure library instance via second parameter of `ae::load()`. æ will pass it to class constructor or object factory by value:

```php
$lib_options = ae::load('ae/options.php', 'my_library_namespace');
	
// That is identical to:
ae::import('ae/options.php');
$lib_options = new aeOptions('my_library_namespace');
```

æ does not "automagically" guess what class to use. You must use `ae::invoke()` method at the beginning of the loaded file to tell æ how and when you want it to instantiate an object:

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

- - - - -

## Stock libraries

### Buffer

`aeBuffer` is a core utility class for capturing output in an exception–safe way:

```php
// Create a buffer and start capturing output:
$buffer = new aeBuffer();

echo 'Hello world!';

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

Buffers can also be used as templates, e.g. when mixing HTML and PHP code:

```html
<?php $buffer = new aeBuffer() ?>
<p><a href="{url}">{name}</a> has been views {visits} times.</p>
<?php $buffer->output(array(
	'url' => $article->url,
	'name' => (strlen($article->name) > 20 ? substr($article->name, 0, 19) . '&hellip;' : $article->name),
	'visits' => number_format($article->visits)
)) ?>
```


### Options

...

### Container

...

### Switch

`aeSwitch` is a core utility class that lets you switch the value of a variable to something else:

```php
echo $foo; // echoes 'foo'

$switch = new aeSwitch($foo, 'bar');

echo $foo; // echoes 'bar'

unset($switch);

echo $foo; // echoes 'foo' again
```

- - - - -

## Licence

Copyright 2011–2012 Anton Muraviev <chromice@gmail.com>

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and
limitations under the License.




	

