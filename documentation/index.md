
# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, and database operations.

This project has been created and maintained by its sole author to explore, validate and express his views on web development. As a result, this is an opinionated codebase that adheres to a few basic principles:

- **Simplicity:** There are no controllers, event emitters and responders, filters, template engines, etc. There is no config file to tinker with. All libraries come with their configuration options set to reasonable defaults.
- **Reliability**: All examples in this documentation are tested and their output verified. [Documentation](index.php) is the spec, [examples](../documentation) are unit tests. The syntax is designed to be expressive and error-resistant. 
- **Performance:** Libraries are loaded when you need them and there is no hidden layer above your code to worry about. Cached responses are served by Apache alone, i.e. without PHP overhead.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guide or standard. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you are just looking for a simple PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com). 

æ will be perfect for you, if your definition of a web application falls along these lines:

> **Opinion:** A web application is a bunch of scripts thrown together to concatenate an HTTP string in response to another HTTP string.

In other words, æ is designed to be as simple as possible, but not simpler. It will not let you forget that most of the back-end programming you do is a glorified string manipulation, but it will remove the most cumbersome aspects of it. 

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
        - [Separate different kinds of logic](#separate-different-kinds-of-logic)
        - [Break your app into components](#break-your-app-into-components)
        - [Keep your template code DRY](#keep-your-template-code-dry)
- [Library reference](#library-reference)
    - [Loader](#loader)
    - [Options](#options)
    - [Path](#path)
    - [File](#file)
    - [Directory](#directory)
    - [Database](#database)

* * *

<a name="tests-and-code-coverage"></a>

**Tests**: 64 out of 68 passed (**94.12%** passed)  
**Code coverage**: 6 out of 10 files covered (**71.75%** average coverage)

| File                | Coverage |
|---------------------|---------:|
| ../ae/loader.php    | 65.73%   |
| ../ae/options.php   | 75%      |
| ../ae/file.php      | 0%       |
| ../ae/container.php | 100%     |
| ../ae/request.php   | 76.6%    |
| ../ae/response.php  | 29.59%   |
| ../ae/image.php     | 0%       |
| ../ae/form.php      | 0%       |
| ../ae/session.php   | 0%       |
| ../ae/database.php  | 83.6%    |

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

You can download the latest release manually, drop it into your project and include <samp>ae/loader.php</samp>:

```php
require 'path/to/ae/loader.php';
```

### Configuring Composer

If you are using [Composer](https://getcomposer.org), make sure your <samp>composer.json</samp> references this repository AND has æ added as a requirement:

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

include 'path/to/ae/loader.php';

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

æ is biased towards imperative style of programming.


Most methods are chainable, including all setters: 


```php
<?php

ae::options('app')
    ->set('name', 'Application')
    ->set('description', 'Blah-blah-blah...');

```

Some libraries operate on the buffered output, and don't have a corresponding setter at all:


```php
<?php

$response = ae::response('text');

echo "Hello World";

$response
    ->cache(\ae\ResponseCache::year)
    ->dispatch();

```

Most of æ code follows these two patterns: 

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


### Exception safety

In order to make your code exception safe, you must be aware the object life cycle.

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


### Everything is a script

> All the world's a stage,   
> And all the men and women merely players.

Strictly speaking æ is not a framework, because it imposes no rules on how your should structure your code: there are no canonical directory structure or file-naming conventions. As far as the author is concerned, all your code may be happily contained in a single <samp>index.php</samp> or be equally happily spread across dozens of directories and hundreds of files.

It would not be unreasonable to assume that your app will be made of one or more PHP scripts responsible for at least one of the following tasks:

- *Handling requests*, i.e. determine what to do based on request URI, GET/POST parameters, form values, etc.
- *Operating on internal state*, e.g. reading/writing files, cookies, session variables, database records, etc.
- *Generating responses*, i.e. spitting out a string giant string conforming to HTTP.

The author does not want to be unfairly prescriptive, so here are just a few tips you may find helpful:

#### Separate different kinds of logic

Here is the top tip for one-file-to-rule-them-all approach: Process input first; *than* execute database queries, check internal state and pre-calculate values; *and than* use those values to generate a response.


```php
<?php

// ==================
// = Handle request =
// ==================
$filters = array(
    'offset' => !empty($_GET['offset']) ? (int) $_GET['offset'] : 0,
    'total' => !empty($_GET['total']) ? (int) $_GET['total'] : 100
);
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

æ lets you either delegate requests to a directory or a file, or process it in anonymous callback function. Typically the first (few) segment(s) should determine the script that should handle the request, while the remainder of the segments further qualify what kind of request it is and specify its parameters.

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

include 'path/to/ae/loader.php'

$route = ae::request()->route(array(
    // 1. Map account to /responders/account.php script
    '/account' => 'path/to/responders/account.php',
    
    // 2. Handle products request here
    '/products/{numeric}' => function ($product_id) {
        echo "Display product #{$product_id}.";
    },
    '/products/page/{numeric}' => function ($page) {
        echo "List product page #{$page}.";
    },
    '/products' => function ($page) {
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
    
));

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

æ takes advantage of the fact that PHP itself is a powerful template engine and exposes two classes of objects to help you keep your presentation code [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself):

1. **Snippet**: for when several scripts are presenting similar looking data. Think: article listings, user profiles, etc.
2. **Container**: for when several scripts are contained within the same template. Think: standard header + various content + standard footer.

##### Snippets

A snippet is a parameterized template, used to present snippets of information in a standardized form.


```php
<?php

$glossary = array(
    'Ash' => 'a tree with compound leaves, winged fruits, and hard pale timber, widely distributed throughout north temperate regions.',
    'Framework' => 'an essential supporting structure of a building, vehicle, or object.',
    'PHP' => 'a server-side scripting language designed for web development but also used as a general-purpose programming language.'
);

ae::output('path/to/snippet.php', array(
    'data' => $glossary
));
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


```html
<dl>
    <dt>Ash</dt>
    <dd>a tree with compound leaves, winged fruits, and hard pale timber, widely distributed throughout north temperate regions.</dd>
    <dt>Framework</dt>
    <dd>an essential supporting structure of a building, vehicle, or object.</dd>
    <dt>PHP</dt>
    <dd>a server-side scripting language designed for web development but also used as a general-purpose programming language.</dd>
</dl>

```

##### Containers

A container is a parameterized template, used as a wrapper. Here's an example of a container:



```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <?= $content ?>
</body>
</html>
```

Another script can use it:


```php
<?php 

$container = ae::container('path/to/container.php')
    ->set('title', 'Container example');

?>
<h1>Hello World!</h1>

```

Which will result in:


```html
<!DOCTYPE html>
<html>
<head>
    <title>Container example</title>
</head>
<body>
    <h1>Hello World!</h1>
</body>
</html>
```

**NB!** The container object is assigned to `$container` variable. The object will persists while the script is being executed, allowing container to capture the content. The container script is always executed *after* the contained script.

# Library reference

## Loader


```diff
Unexpected output: 100_Loader_loading/output.txt
================================================
-barfoobarbar
+
+Fatal error: Uncaught exception 'ae\Exception' with message 'Could not resolve path: library' in /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php:164
+Stack trace:
+#0 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(61): ae::resolve('library', true)
+#1 /Users/chromice/Sites/dev/ae/ae-framework/documentation/100_Loader_loading/index.php(8): ae::register('library', '/Users/chromice...', Array)
+#2 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(403): require('/Users/chromice...')
+#3 /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php(338): ae\_include('/Users/chromice...', NULL)
+#4 /Users/chromice/Sites/dev/ae/ae-framework/ae/request.php(426): ae::output('/Users/chromice...')
+#5 /Users/chromice/Sites/dev/ae/ae-framework/index.php(32): ae\Router->follow()
+#6 {main}
+  thrown in /Users/chromice/Sites/dev/ae/ae-framework/ae/loader.php on line 164
+

```

æ loader is the main script responsible for managing libraries and auto-loading classes. Unless you installed æ via [Composer](#configuring-composer), you need to include the loader manually:


```php
require 'path/to/ae/loader.php';
```

This will import `ae` class into global namespace and a few utility classes into `\ae\` namespace.

### Loading libraries

Out of the box, you can load any core library (e.g. <samp>library</samp>) using global class `ae`:


```php
$lib = ae::library();
```

This imports <samp>ae/library.php</samp> – if it has not been imported yet – which declares all classes and functions it needs to run, and instructs æ how to invoke this library via `ae::invoke()`:


```php
<?php

ae::invoke('Library');

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

### Registering libraries

æ allows you to register your own libraries using `ae::register()` method:


```php
ae::register('library', 'path/to/library.php', array(
    'Library',
    'AnotherLibraryClass'
));
```

The method takes three arguments:

1. Library name (must be unique)
2. Absolute path to library script that contains `ae::invoke()` statement.
3. (Optional) array of [fully qualified class names][php-namespaces].

[php-namespaces]: http://php.net/manual/en/language.namespaces.dynamic.php

Provided you specified all class names, æ will automatically import you library, if you try to use any of those classes:


```php
echo AnotherLibraryClass::bar(); // echo 'bar'
```

### Importing code

If you just want to import configuration settings or helper functions, you can use `ae::import()` method:


```php
ae::import('path/to/helper.php');
```

This will import <samp>helper.php</samp>. If it has been imported already, this method will do nothing.


## Options

Many libraries are using options library to allow you to change their behavior. For instance, the database library can log all queries, time how long they take and measure how much memory they consume. As it is only useful for debugging, this feature is turned off by default.

The database library defines its options and default values next to the `ae::invoke()` statement at the top of its main script:

```php
ae::options('ae::database', array(
	'log' => false
));
```

In your code, you can get the current option value:

```php 
$is_logged = ae::options('ae::database')->get('log');
```

or change it to another value:

```php
ae::options('ae::database')->set('log', true);
```

You can, of course, define your own options:



```php
ae::options('my_application', array(
    'title' => 'My awesome app',
    'version' => '0.93'
));
```

and use those throughout your app:


```php
$o = ae::options('my_application');
echo $o->get('title') . ' v' . $o->get('version');
```

Output:


```txt
My awesome app v0.93
```


## Path

æ operates on absolute paths only. In practice you would want to use this library to define all paths relative to some root directory path:

```php
ae::options('ae::path')->set('root', __DIR__);

echo ae::path('relative/path')->path('to/file.php');
// echo __DIR__ . '/relative/path/to/file.php';
```

Both `\ae\Path::__construct()` and `\ae\Path::path()` accept one or more path components. The following is an alternative way to specify the path from the first example:

```php
echo ae::path('relative/path', 'to/file.php');
```

You can check, if given path exists, and if it is a directory or a file:

```php
if ($path->exists()) {
    $is_directory = $path->is_directory();
    $is_file = $path->is_file();
}
```

Using this library adds an additional layer of security (granted a very thin one), because the resolved path is guaranteed to be contained within the root path:

```php
// The following line throws \ae\PathException
ae::path('../some/path'); 

// While the following is perfectly fine
ae::path('directory', '../some/path');
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


## File

File library is a very wrapper around standard file functions: `fopen()`, `fclose()`, `fread()`, `fwrite`, `copy`, `rename()`, `is_uploaded_file()`, `move_uploaded_file()`, etc. All methods throw `\ae\FileException` on error.

```php
$file = ae::file(__DIR__ . '/file.txt');
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
$file = ae::file(__DIR__ . '/file.txt');

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

Meta data is never saved to the file system, and should only be used by different parts of your application to exchange information associated with the file.

```php
$path = $file->path();
$dir = $file->parent(); // returns parent directory
```


## Directory

```php
$dir = ae::directory(__DIR__);

$filter = '*.txt';

$dir->entries($filter); // return Path array of matching entries (files and directories)
$dir->directories($filter); // return Directory array of matching subdirectories
$dir->files($filter); // return File array of matching files
$dir->parent();
$dir->path();

if (!$dir->exists())
{
    $dir->create(); // recursively creates this and any missing parents
}
else
{
    $dir->delete(); // recursively deletes this directory and all its content
}

// If directory does not exist, open() will create it as well.
$file = ae::path($dir, 'file-name.ext')->open('a');

$name = $dir->name();
$dir->name($name); // rename directory

$dir['meta'] = 'value';
```

* * *

## Buffer

## Snippet

## Layout

* * *

## Request

## Response

## Cache

* * *

## Image

## Form

* * *

## Session

## Database


Database library simplifies building MySQL queries and exposes a simple abstraction for tables and transactions.

Before you can make queries to the database, you have to specify the connection parameters using Options library:


```php
// Configure the "default" database connection
ae::options('ae.database.default')
    ->set('host', 'localhost')
    ->set('user', 'root')
    ->set('password', 'root')
    ->set('database', 'ae');
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
ae::options('ae.database')
    ->set('log', true);
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
    ->aliases(array(
        'table' => 'authors'
    ))
    ->make();
```

Instead of specifying the table name in the query itself we are using `{table}` placeholder and specify its value via `\ae\Database::aliases()` method. The library will wrap the name with backticks ("`") and replace the placeholder for us.

While not particularly useful in this example, placeholders are generally a good way to keep you query code readable.

Let's fill this table with some data:


```php
ae::database()
    ->query("INSERT INTO {table} ({data:names}) VALUES ({data:values})")
    ->aliases(array(
        'table' => 'authors'
    ))
    ->data(array(
        'name' => 'Richar K. Morgan', // (sic)
        'nationality' => 'British'
    ))
    ->make();

$morgan_id = ae::database()->insert_id();
```

In this example we are using `{data:names}` and `{data:values}` placeholders and specify column names and corresponding values via `\ae\Database::data()` method. Now, I intentionally made a typo in the authors name, so let's fix it:


```php
ae::database()
    ->query("UPDATE {table} SET {data:set} WHERE `id` = {author_id}")
    ->aliases(array(
        'table' => 'authors'
    ))
    ->data(array(
        'name' => 'REPLACE(`name`, "Richar ", "Richard ")'
    ), \ae\Database::statement) // don' escape
    ->variables(array(
        'author_id' => $morgan_id
    ), \ae\Database::value) // escape
    ->make();
```

In this example we are using `{data:set}` placeholder and specifying its value via `\ae\Database::data()` method, while `\ae\Database::variables()` method will escape the value of `$morgan_id` and replace `{author_id}` placeholder. 

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
$stephenson->load(array('name', 'nationality'));

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
$shaky = Authors::create(array(
    'name' => 'William Shakespeare',
    'nationality' => 'English'
));

// Create a new record in the database
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
    ->aliases(array(
        'table' => 'books'
    ))
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

<!-- Generated on 28 December 2014 14:04:32 -->