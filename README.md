# æ

æ |aʃ| is a low-level web framework written in PHP with a simple goal behind it: *Make a framework that does as little as possible,  but not less*. The actual code base is minuscule and only what you are actively using is loaded and being kept in memory. 

It requires PHP version 5.3 or higher, and a recent version of MySQL and Apache with mod_rewrite.

- [Acknowledgments](#acknowledgments)
- [Getting started](#getting-started)
- [Core](#core)
	- [Importing code](#importing-code)
	- [Running code](#running-code)
	- [Loading libraries](#loading-libraries)
- [Buffer](#buffer)
- [Container](#container)
- [Options](#options)
- [Log](#log)
- [Probe](#probe)
- [Request](#request)
- [Response](#response)
- [Image](#image)
- [Form](#form)
	- [Field types](#field-types)
	- [Validation](#validation)
- [Database](#database)
	- [Making queries](#making-queries)
	- [Transactions](#transactions)
	- [Retrieving data](#retrieving-data)
	- [Active record](#active-record)
	- [Relationships](#relationships)
- [License](#license)


## Acknowledgments

This project is born out of love and respect for PHP, a language of insanity, learning which helped me build beautiful things. This work stands on the shoulders of giants:

- Respect to Rick Ellis for CodeIgniter and for shifting the perception of how big the framework should be and what it should do towards smaller and more focused.

- MODx community. MODx template syntax and architecture is even uglier than PHP's, but out of your love for the platform many great ideas were born. A few of those — expressed in slightly better PHP — found new home here; some inspired me to come up with better ones.

- Wordpress project, which I've learned to love to hate as a developer, but respect as a user.

## Getting started

Here is a very a simple æ application:

```php
<?php
	include 'ae/core.php';
	
	echo 'Hello ' . ae::request()->segment(0, "world") . '!';
?>
```

You should put this code into a file named *index.php* in the root web directory. */ae* directory containing all the core and libraries should be placed there as well. For the request library to work properly you need to instruct Apache to redirect all unresolved URIs to *index.php*, by adding the following rules to *.htaccess* file:

```apache
<IfModule mod_rewrite.c>
	RewriteEngine on
	RewriteBase /

	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Let's assume the address of this app is *http://localhost/*. If you enter that into the address bar of your web browser, you should see this:

```markdown
Hello world!
```

If you change the address to *http://localhost/universe*, you should see:

```markdown
Hello universe!
```

Congratulations! You may tinker with the examples (see */examples* directory) or read the rest of this document to get a basic understanding of æ capabilities.


## Core

In order to start using æ in your application, you must include *core.php* located in the */ae* directory:

```php
include 'ae/core.php';
```

This will import the `ae` class. Its sole purpose is to manage code: import classes, run scripts and capture their output, register modules and load libraries. All these methods accept both absolute and relative file paths.

If you want æ to look for a file in a specific directory, you can register it:

```php
ae::register('/absolute/path/to/module/directory/');
```

Now æ will look for files in that directory as well as */ae* and root directories:

```php
echo ae::resolve('foo.php');
// will echo "/absolute/path/to/module/directory/foo.php", if the file exists.
```

### Importing code

You can use `ae::import()` method to include any PHP script:

```php
ae::import('path/to/library.php'); 
```

æ will resolve the path and include the script, if it has not been included yet.

### Running code

`ae::render()` returns output of any script as a string:

```php
$output = ae::render('/your/page.php', array(
	'title' => 'Example!',
	'body' => '<h1>Hello world!</h1>'
));
```

Provided the content of */your/page.php* is:

```php
<title><?= $title ?></title><body><?= $body ?></body>
```

The `$output` variable would contain:

```html
<title>Example!</title><body><h1>Hello world!</h1></body>
```

In order to echo the output of a script, you can use `ae::output()` method:

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
```

Which is the same as:

```php
ae::import('ae/options.php');
$options = new aeOptions();
```

You can configure the library instance via second parameter of `ae::load()`. æ will pass it to class constructor or object factory by value:

```php
$lib_options = ae::load('ae/options.php', 'my_library_namespace');
```

That would be identical to:

```php
ae::import('ae/options.php');
$lib_options = new aeOptions('my_library_namespace');
```

æ does not "automagically" guess what class to use, when you are using `ae::load()` method. Instead, you must use `ae::invoke()` method at the beginning of the loaded file to tell æ how and when you want it to instantiate an object.

In order to create a new instance of `LibraryClassName`, every time the library is loaded, you should pass the class name as the first argument:

```php
ae::invoke('LibraryClassName');

class LibraryClassName
{
	function __construct($parameters = null) {}
}
```

If you want to have one and only one instance of the class, you can instruct æ to follow the singleton pattern:

```php
ae::invoke('SingletonClassName', ae::singleton);

class SingletonClassName
{
	function __construct() {}
}
```

**NB!** For the sake of behaviour consistency singletons are not configurable via the `ae::load()` method.

You can also use the factory pattern and delegate the creation of the instance to a function:

```php
ae::invoke('a_factory_function', ae::factory);

function a_factory_function($parameters = null)
{
	return new STDClass();
}

```

Both patterns can be combined (note the usage of a static class method as a factory):

```php
ae::invoke(
	array('AnotherSingletonClassName', 'factory'), 
	ae::factory | ae:singleton
);

class AnotherSingletonClassName
{
	public static function factory()
	{
		$class = get_called_class();
		
		return new $class;
	}
}
```

Please consult with the source code of the core libraries for real life examples.

## Buffer

`aeBuffer` is a core utility class for capturing output in an exception-safe way:

You must create a buffer and assign it to a variable, in order to start capturing output:

```php
$buffer = new aeBuffer();
```

All output is captured until you  either call `aeBuffer::output()` method to echo its content or `aeBuffer::render()` method to return its content as a string. If you do not use these methods, buffer's content will be discarded when the instance is destroyed, either manually or when execution leaves the scope. For example, no output is produced by this script:

```php
$buffer = new aeBuffer(); 

echo 'Invisible text.';

unset($buffer);
```

Buffers can also be used as templates, e.g. when mixing HTML and PHP code:

```html
<?php $buffer = new aeBuffer() ?>
<p><a href="{url}">{name}</a> has been viewed {visits} times.</p>
<?php $buffer->output(array(
	'url' => $article->url,
	'name' => (strlen($article->name) > 20 ? substr($article->name, 0, 19) . '&hellip;' : $article->name),
	'visits' => number_format($article->visits)
)) ?>
```

## Container

Container library allows you to wrap output of a script with the output of another script. The container script is executed *after* the contained script, thus avoiding many problems of using separate header and footer scripts to keep the template code [DRY](http://en.wikipedia.org/wiki/DRY).

Here's an example of HTML container, e.g. *container_html.php*:

```html
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


## Options

Options library is used by many core libraries and allows you to change their behaviour. Options for each library are contained in a separate name space. In order to set or get option value, you must load options library for that namespace:

```php
$options = ae::options('namespace');
```

For example, if your app is sitting behind a proxy or load balancer, you must specify their IP addresses using `aeOptions::set()` method for the request library be able to return the correct IP address of the client:

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


## Log

Log library allows you to log events and dump variables for debugging purposes. It also automatically captures all uncaught exceptions, as well as all errors and notices. On shutdown, it appends the log as HTML comment to the output sent to the browser, or pipes it to standard error output in [CLI mode](http://php.net/manual/en/features.commandline.php). Optionally, the log can be appended to a file in a user-defined directory.

**NB!** The parsing errors and anything the impedes PHP execution in a horrible manner will prevent log library to handle the error. This is a limitation of PHP.

Only logs containing errors are displayed and preserved by default. In order to display and preserve all logs, you should configure the library like this:

```php
ae::options('log')->set('level', aeLog::everything);
```

Here's a short example of how the library should be used:

```php
// You must import the library first
ae::import('ae/log.php');

// Put all logs to /log directory (if it is writable)
ae::options('log')->set('directory', '/log');

// Trigger an error artificially.
trigger_error("Everything goes according to the plan.", E_USER_ERROR);
	
// You can log a message and dump a variable
ae::log("Let's dump something:", $_SERVER);
```

In the context of a web site or application, æ appends the log to the output only if the client IP address is in the white list, which by default contains only 127.0.0.1, a.k.a. localhost.

```php
ae::options('log')->set('ip_whitelist', '127.0.0.1, 192.168.1.101');
```

You can also dump the environment variables for each request like this:

```php
ae::options('log')->set('environment', true);
```

If the request has `X-Requested-With` header  set to `XMLHTTPRequest` (colloquially referred to as AJAX), instead of appending the log to the body of the response, æ will encode it into base64 and send it back via `X-ae-log` header.

æ comes with a small HTML application called **Inspector**. It allows you to browse all logs generated for the current page, including iFrames or AJAX requests. Just make sure that */inspector* directory is located in the web root and log library will inject the inspector button into the page, if there are any message logged. Pressing that button will open a new window, containing all messages:

![](inspector/example.png)

## Probe

Probe library allows you to profile your code and see how much time and memory each part consumes. The results are logged via `ae::log()` method, so you have to have log library imported to see them.

```php
$probe = ae::probe('foo');

usleep(3000);

$probe->report('slept for 3ms');

$a = array(); $j = 0; while($j < 10000) $a[] = ++$j;

$probe->report('filled memory with some garbage');

unset($a);

$probe->report('cleaned the garbage');
```

If you run this script, the probe will log the following messages:

```markdown
foo was created. Timestamp: 0ms (+0.000ms). Footprint: 683kb (+0b).

foo slept for 3ms. Timestamp: 3ms (+3.379ms). Footprint: 684kb (+572b).

foo filled memory with some garbage. Timestamp: 10ms (+6.561ms). Footprint: 1mb (+768kb).

foo cleaned the garbage. Timestamp: 11ms (+1.203ms). Footprint: 685kb (-767kb).

foo was destroyed. Timestamp: 11ms (+0.095ms). Footprint: 686kb (+696b).
```

## Request

Request library allows you to handle both HTTP and command line requests. You can distinguish between different kinds of requests via `aeRequest::cli`, `aeRequest::ajax` and `aeRequest::method` constants:

```php
ae::import('ae/request.php');

if (aeRequest::cli)
{
	echo "Hello World!";
}
else if (aeRequest::ajax)
{
	echo "{message:'Hello world'}";
}
else
{
	echo "<h1>Hello World!</h1>";
	
	if (aeRequest::method === 'GET')
	{
		echo "<p>Nothing to get.</p>";
	}
	else if (aeRequest::method === 'POST')
	{
		echo "<p>Nothing to post.</p>";
	}
}
```

You can access URI segments using `aeRequest::segment()`  method:

```php
// GET /some/arbitrary/request HTTP/1.1
$request = ae::request();

echo $request->segment(0); // some
echo $request->segment(1); // arbitrary

echo $request->type(); // html
echo $request->segment(99, 'default value'); // default value
```

For code portability reasons, you should probably do the same for command line arguments:

```php
// php index.php separate arguments with spaces
$request = ae::request();

echo $request->segment(0); // separate
echo $request->segment(1); // arguments
```

All requests have a type ("html" by default), which is defined by the *file extension* part of the URI.

```php
// GET /some/arbitrary/request.json HTTP/1.1
$request = ae::request();

echo $request->type(); // json
```

Requests can be re-routed to a specific directory:

```php
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

Now, if you have a matching request handler in the */handlers* directory (article.php in this case), æ will run it:

```php
// article.php
$request = ae::request();

if (!$request->is_routed())
{
	die('Direct request is not allowed!');
}

// NB! The /article/ part is pushed out because of routing.
$id = $request->segment(0);

echo "Article ID is $id. ";
echo "You can access it at " . aeRequest::uri();
```

And, finally, use `aeRequest::ip_address()` to get the IP address of the client. If your app is running behind a reverse-proxy or load balancer, you need to specify its IP address via request options:

```php
ae::options('request')->set('proxies', '83.14.1.1, 83.14.1.2');

$client_ip = ae::request()->ip_address();
```

## Response

Response library allows you to create a response of a specific mime-type, set its headers and (optionally) cache and compress it.

Here is an example of a simple application that creates gzip'ed response with a custom header that is cached for 5 minutes:

```php
<?php 
// GET /hello-world HTTP/1.1
include 'ae/core.php';

ae::options('response')
	->set('compress', true); // turn on the g-zip compression

$response = ae::response('html')
	->header('X-Header-Example', 'Some value');
?>
<h1>Hello world</h1>
<?php 
$response
	->cache(5) // cache for five minutes
	->save('/hello-world.html') // save response for /hello-world request
	->dispatch();
?>
```

You can specify the type when you create a new response object. It should be either a valid mime-type or a shorthand like "html", "css", "javascript", "json", etc. By default all responses are "text/html".

When response is created, it starts capturing all output. You have to call `aeResponse::dispatch()` method to send the response, otherwise it will be discarded.

You can set HTTP headers at any point via `aeResponse::header()` method. 

If you want the response to be cached client-side for a number of minutes, use `aeResponse::cache()` method. It will set "Cache-Control", "Last-Modified" and "Expires" headers for you.

Response library supports server-side caching as well. The responses are saved to */cache* directory by default. For caching to work correctly this directory must exist and be writable. You must also configure Apache to look for cached responses in this directory.

Here are the rules that *.htaccess* file in the web root directory must contain:

```apache
<IfModule mod_rewrite.c>
	RewriteEngine on
	RewriteBase /

	# Append ".html", if there is no extension...
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_URI} !\.\w+$
	RewriteRule ^(.*?)$ /$1.html [L]

	# ...and redirect to cache directory ("/cache")
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*?)\.(\w+)$ /cache/$1/index.$2/index.$2 [L,ENV=FROM_ROOT:1]
</IfModule>
```

And here's what *.htaccess* file in */cache* directory must be like:

```apache
<IfModule mod_rewrite.c>
	RewriteEngine on

	# If no matching file found, redirect back to index.php
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*) /index.php?/$1 [L,QSA]
</IfModule>
```

Apache would first look for a cached response, and only if it finds no valid response, will it route the request to */index.php*. No PHP code is actually involved in serving cached responses.

In order to save a response use `aeResponse::save()` method, passing the full request URI (including the file extension) via the first argument. You can delete any cached response using `aeResponse::delete()` method.

## Image

Image library is a very light wrapper around standard GD library functions:

```php
$image = ae::image('examples/image/test.jpg');

// Get meta data
$width = $image->width();
$height = $image->height();
$type = $image->type(); // png, jpeg or gif
$mimetype = $image->mimetype();

// Blow one pixel up.
$image
	->crop(1,1)
	->scale($width, null) // scale proportionately.
	->save('tiny_bit.png');

// save() resets state to default, i.e. no crop, scale, prefix, suffix, etc.

// Crop to cover
$image
	->align(aeImage::center, aeImage::middle) // same as align(0.5, 0.5)
	->cover(100, 100)
	->prefix('cropped_')
	->save(); // save as 'cropped_test.jpg'

// Resize to fit (and preserve the result for next operation)
$small = $image
	->fit(320, 320)
	->suffix('_small')
	->save();  // save as 'test_small.jpg'

// Apply colorize filter
// using http://uk3.php.net/manual/en/function.imagefilter.php
$small
	->apply(IMG_FILTER_COLORIZE, 55, 0, 0)
	->dispatch(); // clean all output, set the correct headers, return the image content and... die!
```

## Form

Form library allows you to populate form fields with values, validate posted forms and display error messages.

Each form must have a unique ID. This way the library knows which form has been posted, even if there are multiple pages on the form. The form

In general any form script would consist of three parts: form and field declaration, validation and HTML output.

Let's declare a simple form with three fields (text input, checkbox and select drop-down) and basic validation rules:

```php
$form = ae::form('form-id');

$input = $this->single('text')
	->required('This field is required.')
	->min_length('It should be at least 3 characters long.', 3)
	->max_length('Please keep it shorter than 100 characters.', 100);

$checkbox = $this->single('checkbox')
	->required('You must check this checkbox!');

$options = array(
	'foo' => 'Foo',
	'bar' => 'Bar'
);

$select = $this->single('select')
	->options('Wrong value selected', $options);
```

Now, this declaration is enough to validate the form, has it been submitted:

```php
if ($form->is_submitted())
{
	$is_valid = $form->validate();
	
	if ($is_valid)
	{
		$values = $form->values();
		
		var_dump($values);
	}
}
```

If the form has not been submitted, you may want to populate it with default values:

```php
if (!$form->is_submitted)
{
	$form->value(array(
		'text' => 'Foo'
	));
}
```

The HTML code of the form's content can be anything you like, but you must use `aeForm::open()` and `aeForm::close()` methods instead of `<form>` and `</form>` tags:

```php
<?= $form->open() ?>
<div class="field">
	<label for="text-input">Inter some text:</label>
	<input id="text-input" name="<?= $input->name ?>" value="<?= $input->value ?>">
	<?= $input->error('<em class="error">', '</em>') ?>
</div>
<div class="field">
	<label>
		<input type="checkbox" name="<?= $input->name ?>" value="1" <?= $this->checked() ?>>
		This is a very important checkbox
	</label>
</div>
<div class="field">
	<label for="select-input">Select something (total optional):</label>
	<select name="<?= $select->name ?>" id="select-input">
		<option>Nothing selected</option>
<?php foreach ($options as $key => $value): ?>
		<option value="<?= $key ?>" <?= $select->selected($key) ?>><?= $value ?></option>
<?php endforeach ?>
	</select>
</div>
<div class="field">
	<button type="submit">Submit</button>
</div>
<?= $form->close() ?>
```

### Field types

Form library does not distinguish between various input types of HTML, as it operates only with values. However if a field accepts multiple values (e.g. `<select multiple>` or several `<input type="checkbox">`) you must declare the field appropriately for validation to work.

In cases where the amount of values is arbitrary you must declare the field as `multiple`:

```php
$cb = $form->multiple('checked')
	->required('Check at least one box');
```

You would then output this field like this:

```php
<label><input type="checkbox" name="<?= $cb->name ?>[]" <?= $cb->checked('foo') ?> value="foo"> Foo</label>
<label><input type="checkbox" name="<?= $cb->name ?>[]" <?= $cb->checked('bar') ?> value="bar"> Bar</label>
```

If you need a sequence of fields with the same validation rules, you should use a `sequence` field of predefined minimum and maximum length:

```php
$tags = $form->sequence('tags', 1, 5)
	->min_length('Should be at least 2 character long', 2);
```

The sequence will contain the minimum number of fields required, but you can allow user to control the length via "Add" and "Remove" buttons.

```php
if ($form->value('add') === 'tag')
{
	$tags[] = ''; // Empty by default
}
else if ($index = $form->value('remove')) // NB! intentionally does not work for 0.
{
	unset($tags[$index]);
}
```

And here is what the HTML of this field will look like:

```php
<?php foreach ($tags as $tag): ?>
<div class="field">
	<label for="tag-<?= $tag->index ?>">Tag <?= $tag->index + 1 ?>:</label>
	<input name="<?= $tag->name ?>[<?= $tag->index ?>]" id="tag-<?= $tag->index ?>">
<?php if ($tag->index > 0): ?>
	<button type="submit" name="remove" value="<?= $tag->index ?>">Remove</button>
<?php endif ?>
	<?= $tag->error() ?>
</div>
<?php endforeach ?>
<?php if ($tag->count() < 5): ?>
<p><button type="submit" name="add" value="tag">Add another</button> tag.</p>
<?php endif ?>
```

### Validation

Form library comes with a few validation methods. Each method accept the error message that should be displayed to the user, if validation fails, as the first argument.

Any field can be made required to be filled in by user:

```php
$field->required('This field is required.');
```

If the value of the field must be a number, you can validate its format and minimum and maximum value:

```php
$field->format('Should contain a number`, aeForm::valid_integer)
	->min_value('Must be greater than 1.', 2)
	->max_value('Must be less than 5.', 4);
```

There several number types supported:

- `aeForm::valid_number` -- any numeric value;
- `aeForm::valid_integer` -- an integer number, e.g. -1, 0, 1, 2, 999;
- `aeForm::valid_octal` -- an octal number, e.g. 01, 0755, 02523;
- `aeForm::valid_hexadecimal` -- a hexadecimal number, e.g. 0x1, 0x24, 0xee; 
- `aeForm::valid_decimal` -- a decimal number, e.g. 0.01, 0.02, 25.00;
- `aeForm::valid_float` -- a float number e.g. 10.05, -1.5678e2;

If value is a string you may validate its format and maximum and minimum length:

```php
$field->format('This should be a valid email.', aeForm::valid_email)
	->min_length('Cannot be shorter than 5 characters.', 5)
	->max_length('Cannot be longer than 100 characters.', 100);
```

There several number string formats supported:

- `aeForm::valid_email` -- a valid email address;
- `aeForm::valid_url` -- a valid URL string: protocol and domain;
- `aeForm::valid_url_path` -- a valid URL string: protocol, domain and path;
- `aeForm::valid_url_query` -- a valid URL string: protocol, domain, path and query string;
- `aeForm::valid_ip` -- any valid IP address;
- `aeForm::valid_ip4` -- any valid IPv4 address;
- `aeForm::valid_ip6` -- any valid IPv6 address;
- `aeForm::valid_public_ip` -- any valid public IP address;

You may also you a regular expression to define format:

```php
$field->format('Only lowercase alphabet characters', '/^[a-z]$/');
```

Or you can use an anonymous function as a validator:

```php
$field->custom('Devils are not allowed.', function ($value) {
	return $value == 666;
});
```


## Database

Database library allows you make MySQL queries and exposes a simple active record style abstraction for tables.

Before you can make queries to the database, you have to specify the connection parameters using the options library:

```php
// Configure the "default" database connection
ae::options('database.default')
	->set('host', 'localhost')
	->set('user', 'root')
	->set('password', 'root')
	->set('database', 'ae');
```

Provided the connection parameters are correct and the database ("ae" in this example) exists, you can create a connection and make a query:

```php
$db = ae::database(); // same as ae::database("default");

$db->query("SELECT 1")->make();
```

### Making queries 

Let's create the "authors" table:

```php
ae::database()
	->query("CREATE TABLE IF NOT EXISTS {table} (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`nationality` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8")
	->names(array(
		'table' => 'authors'
	))
	->make();
```

Instead of specifying the table name in the query itself we are using `{table}` placeholder and specify its value via `aeDatabase::names()` method. The library will wrap the name with backticks ("`") and replace the placeholder for us.

While not particularly useful in this example, placeholders are generally a good way to keep you query code readable.

Let's fill this table with some data:

```php
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
```

In this example we are using `{keys}` and `{values}` placeholders and specify keys and values via `aeDatabase::values()` method. Now, I made a typo in the authors name, so let's updated it:

```php
ae::database()
	->query("UPDATE {table} SET {keys=values} WHERE `id` = {author_id}")
	->names(array(
		'table' => 'authors'
	))
	->values(array(
		'name' => 'Richard K. Morgan'
	))
	->variables(array(
		'author_id' => $morgan_id
	))
	->make();
```

In this example we are using `{keys=values}` placeholder and specifying its value via `aeDatabase::values()` method, while `{author_id}` placeholder in conjunction with `aeDatabase::variables()` will escape the value of `$morgan_id`. 

Of course, these are just examples, there is actually a less verbose way to insert and update rows:

```php
ae::database()->update('authors', array(
	'nationality' => 'English'
), array('id' => $morgan_id));
$stephenson_id = ae::database()->insert('authors', array(
	'name' => 'Neal Stephenson',
	'nationality' => 'American'
)); 
$gibson_id = ae::database()->insert('authors', array(
	'name' => 'William Ford Gibson',
	'nationality' => 'Canadian'
));
```

> There is also `aeDatabase::insert_or_update()` method, which you can use to update a row or insert a new one, if it does not exist; `aeDatabase::count()` for counting rows; `aeDatabase::find()` for retrieving a particular row; and `aeDatabase::delete()` for deleting rows from a table. Please consult the source code of the database library to learn more about them.

### Transactions

A sequence of dependant database queries must always be wrapped in a transaction to prevent race condition and ensure data integrity:

```php
// Open transaction
$transaction = ae::database()->transaction();

// ...perform a series of queries...

$transaction->commit();

// ...perform another series of queries...

$transaction->commit();

// Close transaction (rolling back any uncommitted queries)
unset($transaction);
```

This way, if one of your SQL queries fails, it will throw an exception and all uncommitted queries will be rolled back, when the `$transaction` object is destroyed.

**NB!** Only one transaction can exist at a time.


### Retrieving data

Now that we have some rows in the table, let's retrieve and display them:

```php
$authors = ae::database()->query('SELECT * FROM `authors`')->result();
$count = $authors->count();

echo "There are $count authors in the database:\n";

while ($author = $authors->fetch())
{
	echo "- {$author['name']}\n";
}
```

This will produce:

```markdown
There are 3 authors in the database:
- Richard K. Morgan
- Neal Stephenson
- William Ford Gibson
```

Now, let's change the query so that authors are ordered alphabetically:

```php
$authors = ae::database()
	->query('SELECT * FROM `authors` {sql:order_by}')
	->order_by('`name` ASC')
	->result() // return an instance of aeDatabaseResult
	->all(); // return an array of rows
$count = count($authors);

echo "There are $count authors in the database:\n";

foreach ($authors as $author)
{
	echo "- {$author['name']}\n";
}
```

Again, instead of specifying `ORDER BY` clause directly in the query we are using a placeholder for it, that will be filled in only if we specify the clause via `aeDatabase::order_by()` method. 

> Database library has other token/method combinations like this: `{sql:join}` / `join()`, `{sql:where}` / `where()`, `{sql:group_by}` / `group_by()`, `{sql:having}` / `having()` and `{sql:limit}` / `limit()`. They allow you to write complex parameterized queries without concatenating all bits of the query yourself. Please consult the source code of the database library to learn more about them.

Note that we are also using `aeDatabaseResult::all()` method to return an array of results, instead of fetching them one by one in a `while` loop. Please note that `aeDatabaseResult::fetch()` method is the most memory efficient way of retrieving results.

The example above will produce a list of authors in alphabetical order:

```markdown
There are 3 authors in the database:
- Neal Stephenson
- Richard K. Morgan
- William Ford Gibson
```

### Active record

Database library has `aeDatabaseTable` abstract class that your table specific class can extend:

```php
class Authors extends aeDatabaseTable {}
```

This one line of code is enough to start performing basic CRUD operations for that table:

```php
// Create an instance of Authors pointed at Neal Stephenson 
// record in the "authors" table:
$stephenson = Authors::find($stephenson_id);

// Load the data
$stephenson->load();

echo $stephenson->name; // Neal Stephenson
echo ' -- ';
echo $stephenson->nationality; // American
```

As you can see, finding a record does not actually load its data. In some cases you may  want to update some property of an existing record without loading its data:

```php
// Let's change William Gibson's nationality
$gibson = Authors::find($gibson_id);

$gibson->nationality = 'American';

// Update the record in the database
$gibson->save();
```

Let's create a new record and save it to the database:

```php
$shaky = Authors::create(array(
	'name' => 'William Shakespeare',
	'nationality' => 'English'
));

// Create a new record in the database
$shaky->save();
```

In order to retrieve several records from the database, you would make a regular query, but instead of calling `aeDatabase::result()` method, you should call `aeDatabaseTable::many()` method with the name of the table class as the first argument:

```php
$authors = ae::database()
	->query('SELECT * FROM `authors`')
	->many('Authors');
$count = $authors->count();

echo "There are $count authors in the database:\n";

while ($author = $authors->fetch())
{
	echo "- {$author->name}\n";
}
```

```markdown
There are 4 authors in the database:
- Richard K. Morgan
- Neal Stephenson
- William Ford Gibson
- William Shakespeare
```

Now, Shakespeare was a playwright, while the rest of the authors are novelists. Let's delete his record:

```php
$shaky->delete();
```

### Relationships

Let's make things more interesting by introducing a new class of objects: books. First, we need to create a table to store them:

```php
ae::database()->query("CREATE TABLE IF NOT EXISTS `books` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`author_id` int(10) unsigned NOT NULL,
	`title` varchar(255) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8")->make();
```

We also need a class to represent this table. To keep things interesting, we will name it `Novels`. Obviously `aeDatabaseTable` won't be able to guess the name of the table, so we will specify it manually by overriding the `aeDatabaseTable::name()` method:

```php
class Novels extends aeDatabaseTable
{
	public static function name()
	{
		return 'books'; // that is the real name of the table
	}
}
```

> There are several methods you can override like this: `aeDatabaseTable::database()` to return a different database connection object; `aeDatabaseTable::accessor()` to return an array of primary keys; `aeDatabaseTable::columns()` to return an array of data columns.

We could start spawning new books using `Novels::create()` method, like we did with authors, but instead we will incapsulate this functionality into `Authors::add_novel()` method:

```php
class Authors extends aeDatabaseTable
{
	public function add_novel($title)
	{
		$ids = $this->ids();
		
		return Novels::create(array(
			'author_id' => $ids['id'],
			'title' => $title
		))->save();
	}
}
```

Finally, let's add a few books to the database:

```php
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
```

So far so good. Let's add a method to `Novels` class that will return all book records sorted alphabetically:

```php
class Novels extends aeDatabaseTable
{
	public static function name()
	{
		return 'books'; // that is the real name of the table
	}
	
	public static function all()
	{
		return static::database()
			->query('SELECT * 
				FROM {books} 
				JOIN {authors} ON {authors}.`id` = {books}.`author_id`
				ORDER BY {books}.`title`')
			->names(array(
				'books' => static::name(),
				'authors' => Authors::name()
			))
			->using('Authors', 'author')
			->many('Novels');
	}
}
```

Most of this code should be familiar to you. The only novelty is `aeDatabase::using()` method. The query will retrieve data from both "books" and "authors" tables, so we need to instruct the database driver to return "books" data as an instance of `Novels` class, and "authors" data as an instance of `Authors` class (first argument) assigned to `author` property (second argument) of the corresponding novel object.

Let's inventory our novel collection:

```php
$novels = Novels::all();
$count = $novels->count();

echo "Here are all $count novels ordered alphabetically:\n";

while ($novel = $novels->fetch())
{
	echo "- {$novel->title} by {$novel->author->name}\n";
}
```

```markdown
Here are all 9 novels ordered alphabetically:
- Altered Carbon by Richard K. Morgan
- Broken Angels by Richard K. Morgan
- Count Zero by William Ford Gibson
- Cryptonomicon by Neal Stephenson
- Neuromancer by William Ford Gibson
- Mona Lisa Overdrive by William Ford Gibson
- Reamde by Neal Stephenson
- Snow Crash by Neal Stephenson
- Woken Furies by Richard K. Morgan
```

## License

Copyright 2011-2013 Anton Muraviev <chromice@gmail.com>

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
