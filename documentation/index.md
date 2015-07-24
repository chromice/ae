
# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, and database operations.

This project has been created and maintained by its sole author to explore, validate and express his views on web development. As a result, this is an opinionated codebase that attempts to achieve the following goals:

- **Simplicity:** There are no controllers, event emitters and responders, filters, template engines. There are no config files to tinker with, either: all libraries come preconfigured with sensible default values.
- **Reliability**: All examples in this documentation are tested and their output is verified. [Documentation](index.php) is the spec, [examples](../documentation) are unit tests. The syntax is designed to be expressive and error-resistant. 
- **Performance:** All libraries have been designed with performance and effiency in mind. Responses can be cached statically and served through Apache alone.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guide or standard. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you are just looking for a simple PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com). 

æ will be perfect for you, if your definition of a web application falls along these lines:

> **Opinion:** A web application is a bunch of scripts thrown together to concatenate an HTTP string in response to another HTTP string.

In other words, æ will not let you forget that most of the back-end programming is a glorified string manipulation, but it will alleviate the most cumbersome aspects of it. 

In more practical terms, if you are putting together a site with a bunch of forms that save data to a database, æ comes with everything you need.

You may still find it useful, even if you are thinking of web app architecture in terms of dispatchers, controllers, events, filters, etc. The author assumes you are working on something complex and wishes you a hearty good luck.

* * *

- [Tests and code coverage](#tests-and-code-coverage)
- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Manual installation](#manual-installation)
    - [Configuring Composer](#configuring-composer)
    - [Hello world](#hello-world)
- [Design principles](#design-principles)
    - [Imperative and expressive syntax](#imperative-and-expressive-syntax)
    - [Exception safety](#exception-safety)
    - [Everything is a script](#everything-is-a-script)
- [Library reference](#reference)
    - [Core](#core): [`ae::register()`](#register), [`ae::load()`](#load), [`ae:import()`](#import), [`ae::options()`](#options)
    - [File system](#file-system): [`ae::path()`](#path), [`ae::file()`](#file), [`ae::directory()`](#directory)
    - [Output](#output): [`ae::buffer()`](#buffer), [`ae::snippet()`](#snippet), [`ae::container()`](#container)
    - [HTTP](#http): [`ae::request()`](#request), [`ae::response()`](#response)
    - [Image](#image): [`ae::image()`](#image)
    - [Form](#form): [`ae::form()`](#form)
    - [Session](#session): [`ae::session()`](#session)
    - [Database](#database): [`ae::database()`](#database)

* * *

<a name="tests-and-code-coverage"></a>

**Tests**: 58 out of 68 passed (**85.29%** passed)  
**Code coverage**: 4 out of 10 files covered (**72.53%** average coverage)

| File                | Coverage |
|---------------------|---------:|
| ../ae/loader.php    |   56.18% |
| ../ae/options.php   |   75.00% |
| ../ae/file.php      |    0.00% |
| ../ae/container.php |    0.00% |
| ../ae/request.php   |   75.53% |
| ../ae/response.php  |    0.00% |
| ../ae/image.php     |    0.00% |
| ../ae/form.php      |    0.00% |
| ../ae/session.php   |    0.00% |
| ../ae/database.php  |   83.41% |

* * *

## Getting started

### Requirements

<!--
    TODO: Make sure all requirement are correct, i.e.  check older versions of Apache and MySQL
-->

- **PHP**: version 5.4 or higher with *GD extension* for image manipulation, and *Multibyte String extension* for form validation.
- **MySQL**: version 5.1 or higher with *InnoDB engine*.
- **Apache**: version 2.0 or higher with *mod_rewrite* for nice URLs, and (optionally) *mod_deflate* for response compression.


### Manual installation

You can download the latest release manually, drop it into your project and require <samp>ae/loader.php</samp>:

```php
require 'path/to/ae/loader.php';
```

### Configuring Composer

If you are using [Composer](https://getcomposer.org), make sure your <samp>composer.json</samp> references this repository and has æ added as a requirement:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/chromice/ae"
        }
    ],
    "require": {
        "chromice/ae": "dev-develop"
    }
}
```


### Hello world

Let's create the most basic web application. Put this code into <samp>index.php</samp> in the web root directory:


```php
<?php

require 'path/to/ae/loader.php';

echo 'Hello ' . ae::request()->segment(0, "world") . '!';

?>
```

You should also instruct Apache to redirect all unresolved URIs to <samp>index.php</samp>, by adding the following rules to <samp>.htaccess</samp> file:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Now, if you open our app – located at, say, <samp>http://localhost/</samp> – in a browser you should see this:


```txt
Hello world!
```

If you change the address to <samp>http://localhost/universe</samp>, you should see:


```txt
Hello universe!
```


## Design principles

æ was designed with the following principles in mind:

- Imperative and expressive trumps declarative and formulaic.
- Exceptions are awesome.
- [MVC](http://en.wikipedia.org/wiki/Model–view–controller) and [template engines](http://en.wikipedia.org/wiki/Comparison_of_web_template_engines) are an overkill.

As a result, æ *does not* do anything *magically*: 

1. It only does what it is explicitly told. 
2. It fails fast by throwing exceptions on recoverable state errors, and triggering warnings when you are just being silly.
3. It does not try to reinvent the wheel, and expects you to know how wheels work.


### Imperative and expressive syntax

æ is biased towards imperative style of programming. Most of æ code follows these two patterns:

 

1. Transformation: `ae::noun()->verb()->...->verb()` 
2. Invocation: `$noun = ae::noun()->...->noun()`.

There are exceptions, of course, like the query builder:


```php
<?php

$query = ae::database()
    ->select('*')
    ->from('table')
    ->order_by('column ASC');
    
$result = $query->make();

```

All setters and getters follow array access pattern: 


```php
<?php

$app = ae::options('app');

$app['name'] = 'Application';
$app['description'] = 'Blah-blah-blah...';

```

```diff
Unexpected output
=================
 
+Fatal error: Cannot use object of type ae\Options as array in /Users/chromice/Sites/dev/ae/ae-framework/documentation/006_Syntax/options.php on line 5
+

```

Some libraries operate on the buffered output, and don't have a corresponding setter (or getter) at all:


```php
<?php

$response = ae::response('text');

echo "Hello World";

$response
    ->end()
    ->cache(\ae\ResponseCache::year)
    ->dispatch();

```

```diff
Unexpected output
=================
 Hello World
+Fatal error: Call to undefined method ae\Response::end() in /Users/chromice/Sites/dev/ae/ae-framework/documentation/006_Syntax/response.php on line 8
+

```


### Exception safety

In order to make your code exception safe, you must be aware of the object life cycle.

`__construct()` method is called whenever a new object is instantiated. If the object is assigned to a variable, it will persist until either:

1. the variable is `unset()`
2. the scope where the variable was declared is destroyed, either naturally or *when an exception is thrown*
3. execution of the program halts

`__destruct()` method is called when there are no more variables pointing to the object.

Many æ libraries take advantage of the object life cycle. For instance, internal `\ae\ValueSwitch` class is using it to switch values temporarily:


```php
<?php

$foo = 'foo';
echo $foo; // echoes 'foo'

$switch = new \ae\ValueSwitch($foo, 'bar');

echo $foo; // echoes 'bar'

unset($switch);

echo $foo; // echoes 'foo' again


```

Which will output:


```txt
foobarfoo
```

Buffer, container and response libraries all start capturing output in `__construct()` and process it in `__destruct()`. File library is using  `__destruct()` to unlock previously locked files and close their handles. Database library exposes a transaction object that rolls back any uncommitted queries in `__destruct()`.

> **Opinion:** Generally speaking, all resources your object has allocated must be deallocated in the destructor. And if you find yourself cleaning up state after catching an exception, you are doing it wrong.


### Everything is a script <a name="everything-is-a-script"></a>

> All the world's a stage,   
> And all the men and women merely players.

Strictly speaking æ is not a framework, because it imposes no rules on how your should structure your code: there are no canonical directory structure or file-naming conventions. As far as the author is concerned, all your code may be happily contained in a single <samp>index.php</samp> or be equally happily spread across dozens of directories and hundreds of files.

It would not be unreasonable to assume that your app will be made of one or more PHP scripts responsible for at least one of the following tasks:

- *Handling requests*, i.e. deciding what to do based on request URI, GET/POST parameters, form values, etc.
- *Operating on internal state*, e.g. reading from/writing to files, cookies, sessions, database records, etc.
- *Generating responses*, i.e. spitting out a long string conforming to HTTP, HTML and other standards.

The author does not want to be unfairly prescriptive, so here are just a few tips you may find helpful:

#### Separate different kinds of logic 

Here is the top tip for one-file-to-rule-them-all approach: Process input first; *than* execute database queries, check internal state and pre-calculate values; *and than* use those values to generate a response.


```php
<?php

// ==================
// = Handle request =
// ==================
$filters = [
    'offset' => !empty($_GET['offset']) ? (int) $_GET['offset'] : 0,
    'total' => !empty($_GET['total']) ? (int) $_GET['total'] : 100
];
$filters = array_map($filters, function ($value) {
    return max($value, 0);
});


// =============================
// = Operate on internal state =
// =============================
$results = get_results($filters);


// =====================
// = Generate response =
// =====================
?>

<h1>Results</h1>

<?php if (empty($results) || !is_array($results)): ?>
<p>No results to display</p>
<?php else: ?>
<ul>
    <?php foreach ($results as $result): ?>
    <li><?= $result ?> </li>
    <?php endforeach ?>
</ul>
<?php endif ?>
```

In MVC-speak your controller is at the top, and your view is at the bottom.


#### Break your app into components 

æ lets you either delegate requests to a directory, a file, or a callback function. Typically the first (few) segment(s) should determine the script that handles the request, while the remainder of the segments further qualify what kind of request it is and specify its parameters.

For example, you may want to handle user authentication and let:

- <samp>/account/login</samp> – Authentication form.
- <samp>/account/logout</samp> – Log user out.
- <samp>/account/edit</samp> – Edit account information.
- <samp>/account</samp> – Display account information.

You may also have a script to display products:

- <samp>/products[/page/{page-number}]</samp> – Display (paginated) list of products.
- <samp>/products/{product-id}/{product-seo-title}</samp> – Display a product page.

And finally, you may want to display some pages:

- <samp>/</samp> – Display home page.
- <samp>/about-us</samp> – Display about us page.
- <samp>/about-us/team</samp> – List team members.

Now, here's what an <samp>index.php</samp> in the web root directory may look like:


```php
<?php

require 'path/to/ae/loader.php'

$route = ae::request()->route([
    // 1. Map account to /responders/account.php script
    '/account' => 'path/to/responders/account.php',
    
    // 2. Handle products request here
    '/products/{numeric}' => function ($product_id) {
        echo "Display product #{$product_id}.";
    },
    '/products/page/{numeric}' => function ($page) {
        echo "List product page #{$page}.";
    },
    '/products' => function () {
        echo "List product page #1.";
    },
    
    // 3. Map the rest to /responders/pages directory
    '/' => 'path/to/responders/pages'
    // 
    // responders/pages/
    //   index.php -> Home page
    //   about-us/
    //     index.php -> About us page
    //     team.php -> Team page.
    
]);

try {
    $route->follow();
} catch (\ae\RequestException $e) {
    echo 'No page found.';
}

```



```diff
Unexpected output
=================
-Authentication form.
+Display account information.

```

```diff
Unexpected output
=================
-Log user out.
+Display account information.

```

```diff
Unexpected output
=================
-Edit account information.
+Display account information.

```




#### Keep your template code DRY 

æ takes advantage of the fact that PHP itself is a powerful template engine and has two libraries to help you keep your presentation code [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself):

- [Snippet](#snippet) that is a reusable parameterized template, e.g. list header + list item 1 + list item 2 + list item 3, etc.
- [Container](#container) that is a reusable parameterized wrapper, e.g. header + ... + sidebar + footer.

* * *

# Library reference <a name="reference"></a>

## Core <a name="core"></a>


```diff
Unexpected output: 100_Loader_loading/output.txt
================================================
-barfoobarfoobar
+
+Fatal error: Cannot register "/Users/chromice/Sites/dev/ae/ae-framework/documentation/100_Loader_loading/library.php", because it is not a directory. in /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php on line 70
+

```

æ loader is the main script responsible for managing libraries and auto-loading classes. Unless you installed æ via [Composer](#configuring-composer), you need to require the loader manually:


```php
require 'path/to/ae/loader.php';
```

This will import `ae` class into global namespace and a few utility classes into `\ae\` namespace.


### `ae::register()` <a name="register"></a>

æ allows you to register your own libraries using `ae::register()` method:


```php
ae::register('path/to/library.php', [
    'library' => '\ns\Library',
    '\ns\AnotherLibraryClass'
]);
```

The method takes two arguments:

1. Absolute path to file.
2. An array of invocation callbacks for (optionally) each library name.

[php-namespaces]: http://php.net/manual/en/language.namespaces.dynamic.php

Provided you specified all class names, æ will automatically import you library, if you try to use any of those classes:


```php
echo \ns\AnotherLibraryClass::bar(); // echo 'bar'
```


### `ae::load()` <a name="load"></a>

Out of the box, you can load any core library (e.g. <samp>library</samp>) using global class `ae`:


```php
$lib = ae::load('library', $params);
// or 
$lib = ae::library($params);
```

This imports <samp>ae/library.php</samp> – if it has not been imported yet – which declares all classes and functions it needs to run, and instructs æ how to invoke this library via `ae::invoke()`:


```php
namespace ns;

class Library
{
    public function foo()
    {
        echo 'foo';
    }
    
    static public function bar()
    {
        echo 'bar';
    }
}
```

Once the library is loaded you can call any of its public methods:


```php
echo $lib->foo(); // echo 'foo'
echo $lib->bar(); // echo 'bar'
```


### `ae:import()` <a name="import"></a>

If you just want to import configuration settings or helper functions, you can use `ae::import()` method:


```php
ae::import('path/to/helper.php');
```

This will import <samp>helper.php</samp>. If it has been imported already, this method will do nothing.


### `ae::options()` <a name="options"></a>

Many libraries are using options library to allow you to change their behavior. For instance, the database library can log all queries, time how long they take and measure how much memory they consume. As it is only useful for debugging, this feature is turned off by default.

The database library defines its options and default values next to the `ae::invoke()` statement at the top of its main script:

```php
$db_options = ae::options('ae::database', [
	'log' => false
]);
```

In your code, you can get the current option value:

```php 
$is_logged = $db_options['log'];
```

or change it to another value:

```php
$db_options['log'] = true;
```

You can, of course, define your own options:



```php
ae::options('my_application', [
    'title' => 'My awesome app',
    'version' => '0.93'
]);
```

and use those throughout your app:


```php
$o = ae::options('my_application');
echo $o['title'] . ' v' . $o['version'];
```

Output:


```diff
Unexpected output: 101_Options/output.txt
=========================================
-My awesome app v0.93
+
+Fatal error: Cannot use object of type ae\Options as array in /Users/chromice/Sites/dev/ae/ae-framework/documentation/101_Options/index.php on line 9
+

```


## File system <a name="file-system"></a>

### `ae::path()` <a name="path"></a>

æ operates on absolute paths only. In practice you would want to use this library to define all paths relative to some root directory path:

```php
$path = ae::options('ae::path');
$path['root'] = __DIR__;

echo ae::path('relative/path')->path('to/file.php');
// echo __DIR__ . '/relative/path/to/file.php';
```

Both `\ae\Path::__construct()` and `\ae\Path::path()` accept one or more path components. The following is an alternative way to specify the path from the first example:

```php
echo ae::path('relative/path', 'to/file.php');
```

You can check, if an object at given path exists, and whether it is a directory or a file:

```php
if ($path->exists()) {
    $is_directory = $path->is_directory();
    $is_file = $path->is_file();
}
```

This library adds an additional layer of security (granted a very thin one), because the resolved path is guaranteed to be contained within the root path:

```php
// The following line throws \ae\PathException (which extends \ae\FileSystemException)
echo $path->path('../some/path'); 
```

The library also provides a few shortcut methods. It lets you import a script:

```php
ae::path('helpers/helper.php')->import();
```

It also lets you invoke file or directory library for a path:

```php
$dir = ae::path('path/to/dir')->directory();
$file = ae::path('path/to/some_file.php')->file();
```

Using these shortcuts you can create a file or make a directory for a non-existent path:

```php
$path = ae::path('uploads/');

if (!$path->exists()) {
    $path->directory()->create(0775);
}
```


### `ae::file()` <a name="file"></a>

File library is a wrapper for standard file functions: `fopen()`, `fclose()`, `fread()`, `fwrite()`, `copy()`, `rename()`, `is_uploaded_file()`, `move_uploaded_file()`, etc. All methods throw `\ae\FileException` on error.

```php
$file = ae::file(__DIR__ . '/file.txt')
    ->open('w+')
    ->lock()
    ->truncate()
    ->write('Hello World');
    
$file->seek(0);

if ($file->tell() === 0)
{
    echo $file->read();
}

// Unlock file and close its handle
unset($file);
``` 

The library exposes basic information about the file:

```php
$file = ae::file(__DIR__ . '/file.txt');

echo $file->size(); // echo 12
echo $file->mode(); // echo 0666
echo $file->name(); // echo 'file.txt'
echo $file->extension(); // echo 'txt'
echo $file->mime(); // echo 'text/plain'
```

Existing files can be renamed, copied, moved or deleted:

```php
$file = ae::file(__DIR__ . '/file.txt')
    ->create(0775); // touch + chmod

if ($file->exists())
{
    $mode = $file->mode();
    $file->name('new-file.txt')->extension('doc');
    $copy = $file->copy('./file-copy.txt');
    $file->delete();
    $copy->move('./file.txt')->mode($mode);
}
```

The library can handle uploaded files as well:

```php
$file = ae::file($_FILES['file']['tmp_name']);

if ($file->is_uploaded())
{
    $file->move('/destination/' . $_FILES['file']['name']);
}
```

You can assign arbitrary meta data to a file, e.g. database keys, related files, alternative names, etc.:

```php
$file['real_name'] = 'My text file (1).txt';
$file['resource_id'] = 123;

foreach ($file as $meta_name => $meta_value)
{
    echo "{$meta_name}: $meta_value\n";
}
```

Meta data is transient and is never saved to disk.

```php
$path = $file->path();
$dir = $file->parent(); // returns parent directory
```


### `ae::directory()` <a name="directory"></a>

```php
$dir = ae::directory(__DIR__);

$filter = '*.txt';

$dir->entries($filter); // return an array of matching entries (File and Directory objects)
$dir->directories($filter); // return an array of matching subdirectories (Directory objects)
$dir->files($filter); // return an array of matching files (File objects)
$dir->parent();
$dir->path();

if (!$dir->exists())
{
    $dir->create(0755); // recursively creates this and any missing parents
}
else
{
    $dir->delete(); // recursively deletes this directory and all its content
}

// If directory does not exist, open() will create it as well.
$file = $dir->file('file-name.ext')->open('a');

$name = $dir->name();
$dir->name($name); // rename directory

$mode = $dir->mode();
$dir->mode($mode); // change mode, e.g. 0777

$dir['meta'] = 'value';
```


## Output <a name="output"></a>

### `ae::buffer()` <a name="buffer"></a>

`\ae\Buffer` is a core class used for capturing output.

You must create a buffer and assign it to a variable, in order to start capturing output:

```php
$buffer = ae::buffer();
```

All output is captured until you  either call `\ae\Buffer::end()` or `\ae\Buffer::content()` method that returns its content as a string. If you do not use these methods, buffer's content will be flushed when the instance is destroyed, unless you called `\ae\Buffer::autoclear()`:

```php
$silent_buffer = ae::buffer()->autoclear();
```

Buffers can also be used as templates, e.g. when mixing HTML and PHP code:

```html
<?php $template = ae::buffer([
	'url' => '#',
	'name' => 'blah',
	'visits' => 3
]); ?>
<p><a href="{url}">{name}</a> has been viewed {visits} times.</p>
<?= $template; ?>
```


### `ae::snippet()` <a name="snippet"></a>

A snippet is a parameterized template, used to present snippets of information in a standardized form.


```php
<?php

$glossary = [
    'Ash' => 'a tree with compound leaves, winged fruits, and hard pale timber, widely distributed throughout north temperate regions.',
    'Framework' => 'an essential supporting structure of a building, vehicle, or object.',
    'PHP' => 'a server-side scripting language designed for web development but also used as a general-purpose programming language.'
];

echo ae::snippet('path/to/snippet.php', [
    'data' => $glossary
]);
```

Provided <samp>snippet.php</samp> contains:


```php
<?php

if (empty($data) || !is_array($data))
{
    return;
}

?>
<dl>
<?php foreach ($data as $term => $definition): ?>
    <dt><?= $term ?></dt>
    <dd><?= $definition ?></dd>
<?php endforeach ?>
</dl>

```

The script will produce:


```diff
Unexpected output: 004_Template_snippet/output.html
===================================================
-<dl>
-    <dt>Ash</dt>
-    <dd>a tree with compound leaves, winged fruits, and hard pale timber, widely distributed throughout north temperate regions.</dd>
-    <dt>Framework</dt>
-    <dd>an essential supporting structure of a building, vehicle, or object.</dd>
-    <dt>PHP</dt>
-    <dd>a server-side scripting language designed for web development but also used as a general-purpose programming language.</dd>
-</dl>
 
+Fatal error: Uncaught exception 'ae\Exception' with message 'Could not resolve path: ae/snippet.php' in /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php:164
+Stack trace:
+#0 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(176): ae::resolve('ae/snippet.php')
+#1 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(254): ae::import('ae/snippet.php')
+#2 [internal function]: ae::load('ae/snippet.php', '/Users/chromice...', Array)
+#3 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(292): call_user_func_array(Array, Array)
+#4 /Users/chromice/Sites/dev/ae/ae-framework/documentation/004_Template_snippet/index.php(11): ae::__callStatic('snippet', Array)
+#5 /Users/chromice/Sites/dev/ae/ae-framework/documentation/004_Template_snippet/index.php(11): ae::snippet('/Users/chromice...', Array)
+#6 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(403): require('/Users/chromice...')
+#7 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(338): ae\_include('/Users/chromice...', NULL)
+#8 /Users/chromice/Sites/ in /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php on line 164
+

```


### `ae::container()` <a name="container"></a>

A container is a parameterized template, used as a wrapper. Here's an example of a container:



```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
<?php if (!empty($alert)): ?>
    <script>alert(document.querySelector('<?= $alert ?>').textContent);</script>
<?php endif ?>
</head>
<body>
    <?= $content ?>
</body>
</html>
```

Another script can use it:


```php
<?php 

$container = ae::container('path/to/container.php', [
    'title' => 'Container example'
]);

$container['alert'] = 'h1';

?>
<h1>Hello World!</h1>
<?php

// Optional
$container->end();

?>

```

Which will result in:


```diff
Unexpected output: 005_Template_container/output.html
=====================================================
-<!DOCTYPE html>
-<html>
-<head>
-    <title>Container example</title>
-</head>
-<body>
-    <h1>Hello World!</h1>
-</body>
-</html>
+
+Fatal error: Cannot use object of type ae\Container as array in /Users/chromice/Sites/dev/ae/ae-framework/documentation/005_Template_container/index.php on line 7
+

```

**NB!** The container object is assigned to `$container` variable. The object will persists while the script is being executed, allowing container to capture the content. The container script is always executed *after* the contained script.


## HTTP

### `ae:request()` <a name="request"></a>

```php
// HTTP
$ajax = $ae::request()->is_ajax();
$method = ae::request()->method(); // Request::GET, Request::POST, Request::PUT, etc.
ae::request()->is_method(Request::POST);
$scheme = ae::request()->scheme(); // 'http' OR 'https'
$host = ae::request()->host();
$port = ae::request()->port();
$path = ae::request()->path([offset[, length[, 'default']]]); // where offset is pos/neg num; if no offset is specified returns path + '.' + type
$query = ae::request()->query(['name'[, 'default']]); // if no name specified returns all
$data = ae::request()->data(['name'[, 'default']]); // if no name specified returns all

ae::request()->type(); // 'html' by default 
ae::request()->ip_address();
ae::request()->redirect();

// redirect to /login 
ae::request()->url([
	'scheme' => 'https',
	'path' => '/login'
])->redirect();

ae::request()->route([
	// ...
])->follow();
```

Request library allows you to handle both HTTP and command line requests. You can distinguish between different kinds of requests via `\ae\Request::is_cli()`, `\ae\Request::is_ajax()` and `\ae\Request::method()` methods:

```php
if (ae::request()->is_cli())
{
    echo "Hello World!";
}
elseif (ae::request()->is_ajax())
{
    echo "{message:'Hello world'}";
}
else
{
    echo "<h1>Hello World!</h1>";
    
    if (ae::request()->method() === Request::GET)
    {
        echo "<p>Nothing to get.</p>";
    }
    elseif (ae::request()->is_method(Request::POST))
    {
        echo "<p>Nothing to post.</p>";
    }
}
```

You can access request path segments using `\ae\Request::path()`  method:

```php
// GET /some/arbitrary/request HTTP/1.1
$request = ae::request();

echo $request->path(0, 1); // some
echo $request->path(1, 1); // arbitrary
echo $request->path(0, 2); // some/arbitrary
echo $request->path(-1); // request

echo $request->type(); // html
echo $request->path(99, 1, 'default-value'); // default-value
```

All requests have a type ("html" by default), which is defined by the *file extension* part of the URI.

```php
// GET /some/arbitrary/request.json HTTP/1.1
$request = ae::request();

echo $request->path(); // some/arbitrary/request.json
echo $request->type(); // json
```

In order to get the IP address of the client, you should use `\ae\Request::ip_address()` method. If your app is running behind a reverse-proxy or load balancer, you need to specify their IP addresses via request options:

```php
$request_options = ae::options('ae::request');
$request_options['proxy_ips'] = ['83.14.1.1', '83.14.1.2'];

$client_ip = ae::request()->ip_address();
```


#### Routing

Requests can be re-routed to a specific directory:

```php
// GET /article/123 HTTP/1.1
$request = ae::request();

$route = $request->route('/', 'handlers/');

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

$id = $request->path(1, 1);

echo "Article ID is $id. ";
echo "You can access it at " . $request->uri();
```

You can route different types of requests to different directories:

```php
$route = ae::request()->route([
    '/admin' => 'cms/', // all requests starting with /admin 
    '/' => 'webroot/' // other requests
])->follow();
```

You can always provide an anonymous function instead of a directory and pass path segments as arguments like this:

```php
ae::request()->route([
    '/example/{any}/{alpha}/{numeric}' => function ($any, $alpha, $numeric, $etc) {
        echo 'First handler. Request URI: /example/' . $any . '/' . $alpha . '/' . $numeric . '/' . $etc;
    },
    '/' => function($uri) {
        echo 'Default handler. Request URI: /' . $uri;
    }
])->follow();
```


### `ae::response()` <a name="response"></a>

Response library allows you to create a response of a specific mime-type, set its headers and (optionally) cache and compress it.

Here is an example of a simple application that creates gzip'ed response with a custom header that is cached for 5 minutes:

```php
<?php 
// GET /hello-world HTTP/1.1
include 'ae/core.php';

$response_options = ae::options('ae::response');
$response_options['compress_output'] = true; // turn on the g-zip compression

$response = ae::response('html')
    ->header('X-Header-Example', 'Some value');
?>
<h1>Hello world</h1>
<?php 
$response
    ->cache(5, '/hello-world.html') // cache for five minutes as /hello-world.html
    ->dispatch(); // dispatch the request
?>
```

You can specify the type when you create a new response object. It should be either a valid mime-type or a shorthand like "html", "css", "javascript", "json", etc. By default all responses are "text/html". When response object is created, it starts capturing all output. You have to call `\ae\Response::dispatch()` method to send the response along with any HTTP headers set via `\ae\Response::header()` method.


#### Caching

If you want the response to be cached client-side for a number of minutes, use `\ae\Response::cache()` method. It will set "Cache-Control", "Last-Modified" and "Expires" headers for you.

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

In order to save a response use `\ae\Response::cache()` method, passing the number of minutes it should be cached for via first argument and full request URI (including the file extension) via second argument:

```php
$response->cache(5, '/hello-world.html');
```

You can delete any cached response using `\ae\ResponseCache::delete()` method by passing full or partial URL to it:

```php
ae::import('ae/response.php');

\ae\ResponseCache::delete('/hello-world.html');
```

You should also remove all stale cache entries via `\ae\ResponseCache::collect_garbage()`:

```php
ae::import('ae/response.php');

\ae\ResponseCache::collect_garbage();
```

The garbage collection can be a very resource-intensive operation, so its usage should be restricted to an infrequent cron job.


## Image: `ae::image()` <a name="image"></a>

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
    ->align(\ae\Image::center, \ae\Image::middle) // same as align(0.5, 0.5)
    ->fill(100, 100)
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

### Caching

Images can be cached just like responses:

```php
$image = ae::image('examples/image/test.jpg');

$image->apply(IMG_FILTER_COLORIZE, 55, 0, 0)
    ->cache(\ae\ResponseCache::year, '/images/foo.png') // cache image for a year
```


## Form: `ae::form()` <a name="form"></a>

Form library allows you to generate form controls, validate submitted values and display error messages.

Each form must have a unique ID. This way the library knows which form has been submitted, even if there are multiple forms on the page.

In general any form script would consist of three parts: form and field declaration, validation and HTML output.

Let's declare a simple form with three fields (text input, checkbox and select drop-down) and basic validation rules:

```php
$form = ae::form('form-id');

$input = $form->single('text_input')
    ->required('This field is required.')
    ->min_length('It should be at least 3 characters long.', 3)
    ->max_length('Please keep it shorter than 100 characters.', 100);

$checkbox = $form->single('checkbox_input')
    ->required('You must check this checkbox!');

$options =[
    'foo' => 'Foo',
    'bar' => 'Bar'
]);

$select = $this->single('select_input')
    ->valid_value('Wrong value selected.', $options);
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
if (!$form->is_submitted())
{
    $form->value[
        'text' => 'Foo'
    ]));
}
```

The HTML code of the form's content can be anything you like, but you must use `\ae\Form::open()` and `\ae\Form::close()` methods instead of `<form>` and `</form>` tags:

```php
<?= $form->open() ?>
<div class="field">
    <label for="<?= $input->id() ?>">Enter some text:</label>
    <?= $input->input('text') ?>
    <?= $input->error() ?>
</div>
<div class="field">
    <label><?= $checkbox->input('checkbox') ?> Check me out!</label>
    <?= $checkbox->error() ?>
</div>
<div class="field">
    <label for="<?= $select->id() ?>">Select something (totally optional):</label>
    <?= $select->select['' => 'Nothing selected']) + $options) ?>
    <?= $select->error() ?>
</div>
<div class="field">
    <button type="submit">Submit</button>
</div>
<?=  $form->close() ?>
```

Most generated form controls will have HTML5 validation attributes set automatically. If you want to turn off HTML5 validation in the browsers that support it, you should set the `novalidate` attribute of the form:

```php
<?= $form->open['novalidate' => true])); ?>
```

### Field types

Form library does not distinguish between various input types of HTML when the form is validated, as it operates only with values. However, if a field accepts multiple values (e.g. `<select multiple>`, `<input type="email" multiple>` or several `<input type="checkbox">`) you must declare the field appropriately for validation to work.

In cases where the amount of values is arbitrary you must declare the field as `multiple`:

```php
$cb = $form->multiple('checked')->required('Check at least one box');
```

You would then output this field like this:

```php
<label><?= $cb->input('checkbox', 'foo') ?> Foo</label><br>
<label><?= $cb->input('checkbox', 'bar') ?> Bar</label>
<?= $cb->error('<br><em class="error">','</em>') ?>
```

If you need a sequence of fields with the same validation rules, you should use a `sequence` field of predefined minimum and (optionally) maximum length:

```php
$tags = $form->sequence('tags', 1, 5);

$tag_input = $tags->single('tag_input')
    ->min_length('Should be at least 2 character long', 2);

```

The sequence will contain the minimum number of fields required, but you can let user control the length via "Add" and "Remove" buttons.

```php
<?php foreach ($tags as $index => $tag): ?>
<div class="field">
    <label for="<?= $tag->id() ?>">Tag <?= $index + 1 ?>:</label>
    <?= $tag['tag_input']->input('input') ?> <?= $files->remove_button($index) ?>
    <?= $tag['tag_input']->error('<br><em class="error">', '</em>') ?>
</div>
<?php endforeach ?>
<?= $files->add_button() ?>
```

You can combine several sequences in one loop to create repeatable field groups.

### Validation

Form library comes with a few validation methods. Each method accepts validation error message as the first argument.

Any field can be made required:

```php
$field->required('This field is required.');
```

If the value of the field is a decimal/integer number or time/date/month/week, you can validate its format and minimum and maximum value:

```php
$number->valid_pattern('Should contain a number.', \ae\TextValidator::integer)
    ->min_value('Must be equal to 2 or greater.', 2)
    ->max_value('Must be equal to 4 or less.', 4);
$date->valid_pattern('Should contain a date: YYYY-MM-DD.', \ae\TextValidator::date)
    ->min_value('Cannot be in the past.', date('Y-m-d'));
```

If value is a string you may validate its format and maximum and minimum length:

```php
$field->valid_pattern('This should be a valid email.', \ae\TextValidator::email)
    ->min_length('Cannot be shorter than 5 characters.', 5)
    ->max_length('Cannot be longer than 100 characters.', 100);
```

The library comes with a few format validators:

- `\ae\TextValidator::integer` — an integer number, e.g. -1, 0, 1, 2, 999;
- `\ae\TextValidator::decimal` — a decimal number, e.g. 0.01, -.02, 25.00, 30;
- `\ae\TextValidator::numeric` — a string consisting of numeric characters, e.g. 123, 000;
- `\ae\TextValidator::alpha` — a string consisting of alphabetic characters, e.g. abc, cdef;
- `\ae\TextValidator::alphanumeric` — a string consisting of both alphabetic and numeric characters, e.g. a0b0c0, 0000, abcde;
- `\ae\TextValidator::color` — a hex value of a color, e.g. #fff000, #434343;
- `\ae\TextValidator::time` — a valid time, e.g. 14:00:00, 23:59:59.99;
- `\ae\TextValidator::date` — a valid date, e.g. 2009-10-15;
- `\ae\TextValidator::datetime` — a valid date and time, e.g. 2009-10-15T14:00:00-9:00;
- `\ae\TextValidator::month` — a valid month, e.g. 2009-10;
- `\ae\TextValidator::week` — a valid week, e.g. 2009-W42;
- `\ae\TextValidator::email` — a valid email address;
- `\ae\TextValidator::url` — a valid URL string;
- `\ae\TextValidator::postcode_uk` — a valid UK postal code.

You may define any other pattern manually:

```php
$field->valid_pattern('At least 5 alphabetic characters.', '[a-zA-Z]{5,}');
```

You can also use an anonymous function as a validator:

```php
$field->valid_value('Devils are not allowed.', function ($value) {
    return $value != 666;
});
```

If you let user choose a value (or multiple values) from a predefined list, you should always validate whether they submitted correct data:

```php
$field->valid_value('Wrong option selected.',[
    'foo', 'bar' //, '...'
]);
```

Ordinary users would never see this error, but it prevents would-be hackers from tempering with the data.


## Session: `ae::session()` <a name="session"></a>

...


## Database: `ae::database()` <a name="database"></a>


```diff
Expected to find: Query #2: SELECT 1
====================================

Fatal error: Cannot use object of type ae\Options as array in /Users/chromice/Sites/dev/ae/ae-framework/documentation/____Database_test/index.php on line 7

```

Database library simplifies building MySQL queries and exposes a simple abstraction for tables and transactions.

Before you can make queries to the database, you have to specify the connection parameters using Options library:


```php
// Configure the "default" database connection
$connection = ae::options('ae::database(default)');

$connection['host'] = 'localhost';
$connection['user'] = 'root';
$connection['password'] = 'root';
$connection['database'] = 'ae';
```

Provided the connection parameters are correct and the database ("ae" in this example) exists, you can create a connection and make a query:


```php
try {
    $db = ae::database(); // same as ae::database("default");
    
    $db->query("SELECT 1")->make();
} catch (\ae\DatabaseException $e) {
    echo 'Something went wrong: ' . $e->getMessage();
}
```

As you can see, whenever something goes wrong on the database side, the library throws `\ae\DatabaseException`, which you can catch and handle gracefully.

If you want to know what queries are performed and how much memory and time they take, you can turn query logging on:


```php
$db_options = ae::options('ae::database');

$db_options['log'] = true;
```

<!-- TODO: See [Inspector](#inspector) section for more details. -->

### Making queries 


Let's create the "authors" table:


```php
ae::database()->query("CREATE TABLE IF NOT EXISTS {table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `nationality` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8")
    ->aliases([
        'table' => 'authors'
    ])
    ->make();
```

Instead of specifying the table name in the query itself we are using `{table}` placeholder and specify its value via `\ae\Database::aliases()` method. The library will wrap the name with backticks ("`") and replace the placeholder for us.

While not particularly useful in this example, placeholders are generally a good way to keep you query code readable.

Let's fill this table with some data:


```php
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
```

In this example we are using `{data:names}` and `{data:values}` placeholders and specify column names and corresponding values via `\ae\Database::data()` method. Now, I intentionally made a typo in the authors name, so let's fix it:


```php
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
```

In this example we are using `{data:set}` placeholder and specifying its value via `\ae\Database::data()` method, while `\ae\Database::variables()` method will escape the value of `$morgan_id` and replace `{author_id}` placeholder. 

Of course, these are just examples, there is actually a less verbose way to insert and update rows:


```php
ae::database()->update('authors', [
    'nationality' => 'English'
], ['id' => $morgan_id]);
$stephenson_id = ae::database()->insert('authors', [
    'name' => 'Neal Stephenson',
    'nationality' => 'American'
]); 
$gibson_id = ae::database()->insert('authors', [
    'name' => 'William Ford Gibson',
    'nationality' => 'Canadian'
]);
```

> There is also `\ae\Database::insert_or_update()` method, which you can use to update a row or insert a new one, if it does not exist; `\ae\Database::count()` for counting rows; `\ae\Database::find()` for retrieving a particular row; and `\ae\Database::delete()` for deleting rows from a table. Please consult the library source code to learn more about them.

### Transactions

A sequence of dependent database queries must always be wrapped in a transaction to prevent race condition and ensure data integrity:


```php
// Open transaction
$transaction = ae::database()->transaction();
// ...perform a series of queries...
$transaction->commit();
// ...perform another series of queries...
// ...close transaction and roll back uncommitted queries.
unset($transaction);
```

This way, if one of your SQL queries fails, it will throw an exception and all uncommitted queries will be rolled back, when the `$transaction` object is destroyed.

**NB!** Only one transaction can be open at a time.

### Retrieving data

Now that we have some rows in the table, let's retrieve and display them:


```php
$count = ae::database()->count('authors');
$authors = ae::database()->select('authors')->result();

echo "There are $count authors in the database:\n";

while ($author = $authors->fetch())
{
    echo "- {$author['name']} ({$author['nationality']})\n";
}
```

This will produce:


```txt
There are 3 authors in the database:
- Richard K. Morgan (British)
- Neal Stephenson (American)
- William Ford Gibson (Canadian)
```

Now, let's change the query so that authors are ordered alphabetically:


```php
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
```

Again, instead of specifying `ORDER BY` clause directly in the query we are using a placeholder for it, that will be filled in only if we specify the clause via `\ae\Database::order_by()` method. 

> Database library has other placeholder/method combinations like this: `{sql:join}` / `join()`, `{sql:where}` / `where()`, `{sql:group_by}` / `group_by()`, `{sql:having}` / `having()` and `{sql:limit}` / `limit()`. They allow you to write complex parameterized queries without concatenating all bits of the query yourself. Please consult the library source code to learn more about them.

Note that we are also using `\ae\DatabaseResult::all()` method to return an array of results, instead of fetching them one by one in a `while` loop. Please note that `\ae\DatabaseResult::fetch()` method is the most memory efficient way of retrieving results.

The example above will produce a list of authors in alphabetical order:


```txt
There are 3 authors in the result set:
- Neal Stephenson
- Richard K. Morgan
- William Ford Gibson
```

### Active record

Database library has `\ae\DatabaseTable` abstract class that your table specific class can extend:


```php
class Authors extends \ae\DatabaseTable {}
```

This one line of code is enough to start performing basic CRUD operations for that table:


```php
// Create an instance of Authors pointed at Neal Stephenson 
// record in the "authors" table:
$stephenson = Authors::find($stephenson_id);

// Load the data
$stephenson->load(['name', 'nationality']);

echo $stephenson->name; // Neal Stephenson
echo ' -- ';
echo $stephenson->nationality; // American
```

As you can see, finding a record does not actually load its data. In some cases you may  want to update some property of an existing record without loading its data:


```php
$gibson = Authors::find($gibson_id);

$gibson->nationality = 'American';

// Update the record in the database
$gibson->save();
```

Let's create a new record and save it to the database:


```php
$shaky = Authors::create([
    'name' => 'William Shakespeare',
    'nationality' => 'English'
]);

// Save the new record to the database
$shaky->save();
```

In order to retrieve several records from the database, you would make a regular query, but instead of calling `\ae\Database::result()` method, you should call `\ae\DatabaseTable::many()` method with the name of the table class as the first argument:


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

That will output:


```txt
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
ae::database()->query("CREATE TABLE IF NOT EXISTS {table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `author_id` int(10) unsigned NOT NULL,
        `title` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8")
    ->aliases([
        'table' => 'books'
    ])
    ->make();
```

We also need a class to represent this table. To keep things interesting, we will name it `Novels`. Obviously `\ae\DatabaseTable` won't be able to guess the name of the table, so we will specify it manually by overriding the `\ae\DatabaseTable::name()` method:


```php
class Novels extends \ae\DatabaseTable
{
    public static function name()
    {
        return 'books'; // that is the real name of the table
    }
}
```

> There are several methods you can override like this: `\ae\DatabaseTable::database()` to return a different database connection object; `\ae\DatabaseTable::accessor()` to return an array of primary keys; `\ae\DatabaseTable::columns()` to return an array of data columns.

We could start spawning new books using `Novels::create()` method, like we did with authors, but instead we will incapsulate this functionality into `Authors::add_novel()` method:


```php
class Authors extends \ae\DatabaseTable
{
    public function add_novel($title)
    {
        $ids = $this->ids();
        
        return Novels::create([
                'author_id' => $ids['id'],
                'title' => $title
            ])->save();
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
class Novels extends \ae\DatabaseTable
{
    public static function name()
    {
        return 'books'; // that is the real name of the table
    }
    
    public static function all()
    {
        return static::database()
            ->select(self::name())
            ->joining('Authors', 'author')
            ->order_by('{table}.`title`')
            ->many('Novels');
    }
}
```

Most of this code should be familiar to you. The only novelty is `\ae\Database::joining()` method. The query will retrieve data from both "books" and "authors" tables, and we instruct the database driver to return "books" data as an instance of `Novels` class, and "authors" data as an instance of `Authors` class (first argument) assigned to `author` property (second argument) of the corresponding novel object.

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

Which will output:


```txt
Here are all 9 novels ordered alphabetically:
- Altered Carbon by Richard K. Morgan
- Broken Angels by Richard K. Morgan
- Count Zero by William Ford Gibson
- Cryptonomicon by Neal Stephenson
- Mona Lisa Overdrive by William Ford Gibson
- Neuromancer by William Ford Gibson
- Reamde by Neal Stephenson
- Snow Crash by Neal Stephenson
- Woken Furies by Richard K. Morgan
```
