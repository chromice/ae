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
	- [Log](#log)
	- [Probe](#probe)
	- [Request](#request)
	- [Response](#response)
	- [Database](#database)
- [Licence](#licence)

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
echo $options->get('foo'); // echo 'bar'
```

The sole purpose of `ae` class is to enable you to import code, run scripts and capture their output, and load stock or custom libraries. All these methods accept both absolute and relative file paths. By default non–absolute paths must be relative to the directory that contains **ae** directory.

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

In order to stop using the context, simply unset the variable:

```php
unset($context);
```

Many contexts can exist at the same time, but only the lastest one will be used. Once it's destroyed æ will restore the previously active context. It is a generally a good idea to assign contexts to local variables and destroy them as soon as possible.


### Importing code

You can use `ae::import()` method to include any PHP script:

```php
ae::import('path/to/library.php'); 
// æ will resolve the path and include the script, if it 
// has not been included yet.
```

### Running code

`ae::render()` returns output of any script as a string:

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

### Container

Container library allows you to wrap output of a script with the output of another script. The container script is executed *after* the contained script, thus avoiding many problems of using separate header and footer scripts to keep the template code [DRY](http://en.wikipedia.org/wiki/DRY).

Here's an example of HTML container, e.g. *container_html.php*:

```php
<html>
<head>
	<title><?= $title ?></title>
</head>
<body>
	<?= $content ?>
</body>
</html>
```

Another script (e.g. *hellow_world.php*) can use it like this:

```php
<?php 
$container = ae::container('path/to/container_html.php')
	->set('title', 'Container example');
	
?>
<h1>Hello World!</h1>
```

When rendered, it will produce this:

```html
<html>
<head>
	<title>Container example</title>
</head>
<body>
	<h1>Hello World!</h1>
</body>
</html>
```


### Options

Options library is used by many stock libraries and allows you to change their behaviour. Options for each library are contained in a separate name space. In order to set or get option value, you must load options library for that namespace:

```php
$options = ae::options('namespace');
// which is identical to
$options = ae::load('ae/options.php', 'namespace');
```

For example, if your app is sitting behind a proxy or load balancer, you must specify their IP addresses using `aeOptions::set()` method for the request library to return correct IP address of the client:

```php
$options = ae::options('request');

$options->set('proxies', '83.14.1.1, 83.14.1.2');
```

Request library will use `aeRequest::get()` method to retrieve the value of that option:

```php
$options = ae::options('request');

$proxies = $options->get('proxies', null);
```

`aeOptions::get()` returns the value of the second argument (`null` by default), if the option has not been previously set. Thus many options are indeed optional.


### Log

Log library allows you to log events and dump variables for debugging purposes. It also automatically captures all uncaught exceptions, as well as all errors and notices. On shutdown, it appends the log as HTML comment to the output sent to the browser, or pipes it to standard error output in [CLI mode](http://php.net/manual/en/features.commandline.php). Optionally, the log can be appended to a file in a user–defined directory.

```php
// You must import the library first
ae::import('ae/log.php');

// Put all logs to /log directory (if it is writable)
ae::options('log')->set('directory', '/log');

// Now you can log a message and dump a variable
ae::log("Here's a dump of $_SERVER:", $_SERVER);

// All errors, notices will be logged too
trigger_error("Everything goes according to the plan.", E_USER_NOTICE);
```

In the context of a web site or application, æ appends the log to the output only if the client IP address is in the white list, which by default contains only 127.0.0.1, a.k.a. localhost.

```php
ae::options('log')->set('ip_whitelist', '127.0.0.1, 192.168.1.101');
```

You can also dump the environment variables for each request like this:

```php
ae::options('log')->set('environment', true);
```

If the request has `X-Requested-With` header  set to `XMLHTTPRequest` (commonly known as AJAX), instead of appending the log to the body of the response, æ will encode it into base64 and send it via `X-ae-log` header.

### Probe

Probe library allows you to profile your code and see how much time and memory each part consumes. The results are logged via `ae::log()` method, so you have to have log library imported to see them.

```php
$probe = ae::probe('foo');

usleep(3000);

$probe->report('slept for 3ms');

$a = array(); $j = 0; while($j < 10000) $a[] = ++$j;

$probe->report('filled memory with some garbage');

unset($a);
```

When this script is run, test probe will log the following messages:

```
foo started. Timestamp: 0ms (+0.000ms). Footprint: 632156 bytes (+0 bytes).

foo slept for 3ms. Timestamp: 4ms (+3.566ms). Footprint: 632740 bytes (+1032 bytes).

foo filled memory with some garbage. Timestamp: 13ms (+9.337ms). Footprint: 1419204 bytes (+786464 bytes).

foo finished. Timestamp: 14ms (+1.541ms). Footprint: 630084 bytes (-789120 bytes).
```

### Request

Request library allows you to handle both HTTP and command line requests. You can distinguish between different kinds requests using `aeRequest::is()` method:

```php
$request = ae::request();

if ($request->is('cli'))
{
	echo "Hello World!";
}
else if ($request->is('ajax'))
{
	echo "{message:'Hello world'}";
}
else
{
	echo "<h1>Hello World!</h1>";
	
	if ($request->is('get'))
	{
		echo "<p>Nothing to get.</p>";
	}
	else
	{
		echo "<p>Nothing to post.</p>";
	}
}
```

You can access URI segments or command line arguments using `aeRequest::segment()`  method:

```php
// GET /some/arbitrary/request HTTP/1.1
$request = ae::request();

echo $request->segment(0); // some
echo $request->segment(1); // arbitrary

// php index.php separate arguments with spaces
echo $request->segment(0); // separate
echo $request->segment(1); // arguments

echo $request->segment(99, 'default value'); // default value
```

You can also re–route the requests to a specific file or directory. E.g. if you have index.php in the root directory, you could do the following:

```
// GET /article/123 HTTP/1.1
$request = ae::request();

$route = $request->route('handlers/');

if (!$route->exists())
{
	header('HTTP/1.1 404 Not Found');
	echo "Page does not exist.";
	exit;
}

$route->follow();
```

Now, if you have various request handlers in the handlers directory, e.g. article.php in this case, æ will run:

```php
// article.php
$request = ae::request();

// NB! The /article/ part is pushed out because of routing.
$id = $request->segment(0);

echo "Article ID is $id. ";

echo "You can access it at " . $request->base() . "/" . $id;
```

### Response

...

### Database

...

## Licence

Copyright 2011–2012 Anton Muraviev <chromice@gmail.com>

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.




	

