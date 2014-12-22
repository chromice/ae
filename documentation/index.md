
# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, and database operations.

This project has been created and maintained by its sole author to explore, validate and express his views on web development. As a result, this is an opinionated codebase that adheres to a few basic principles:

- **Simplicity:** æ is not a framework. There are no controllers, event emitters and responders, filters, templating engines, etc. There is no config file to tinker with. All libraries come with their configuration options set to reasonable defaults.
- **Reliability**: All examples in this documentation are tested and their output verified. [Documentation](index.php) is the spec, [examples](../documentation) are unit tests. The syntax is designed to be expressive and error-resistant.
- **Performance:** Libraries are loaded when needed and there is no framework-layer above your code to worry about. Cached responses are served by Apache alone, i.e. without PHP overhead.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guides or standards. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you are just looking for a simple PHP framework, keep searching: you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com). 


## Getting started

### Requirements

<!--
    TODO: Make sure all requirement are correct, i.e.  check older versions of Apache and MySQL
-->

- **PHP**: version 5.4 or higher with *GD extension* for image manipulation, and *Multibyte String extension* for form validation.
- **MySQL**: version 5.1 or higher with *InnoDB engine*.
- **Apache**: version 2.0 or higher with *mod_rewrite* for nice URLs, and *mod_deflate* for response compression.


### Manual installation

You can download the latest release manually, drop it into your project and include `ae/core.php`:

```php
require 'path/to/ae/core.php';
```

### Configuring Composer

If you are using [Composer](https://getcomposer.org), make sure your `composer.json` references this repository AND has æ added as a requirement:

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

Let's create the most basic web application. Put this code into `index.php` in the web root directory:


```php
<?php

include 'path/to/ae/core.php';

use \ae\Core as ae;

echo 'Hello ' . ae::request()->segment(0, "world") . '!';

?>
```

You should also instruct Apache to redirect all unresolved URIs to `index.php`, by adding the following rules to `.htaccess` file:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Now, if you open our app – located at, say, `http://localhost/` – in a browser you should see this:


```txt
Hello world!
```

If you change the address to `http://localhost/universe`, you should see:


```txt
Hello universe!
```


## General principles

The key assumptions you have to agree with for us to be friends:

- Exceptions are awesome.
- [Abstractions cause headaches](http://www.joelonsoftware.com/articles/LeakyAbstractions.html).
- [MVC](http://en.wikipedia.org/wiki/Model–view–controller) is not fit for web.
- *Imperative* trumps *declarative* programming in non-trivial cases.

The following principles stem from use cases æ was designed to solve.

### Exception safety: from `__construct()` till `__destruct()`

`__construct()` method is called whenever a new object is instantiated. If the object is assigned to a variable, it will persist until either:

1. the variable is `unset()`
2. the scope where the variable was declared is destroyed, either naturally or when an exception is thrown
3. execution of the program halts

`__destruct()` method is called when all variables pointing to the object instance are no more.

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

Generally speaking, all resources you allocated in your constructor, must be deallocated in the destructor. This way you don't have to rely on user remembering to close file handles or unlock previous locked files, dispatch requests or flush buffers, or do some other important tasks. 

Using object life cycle is what makes your code exception safe.


### PHP is the templating engine

æ takes advantage of the fact that PHP itself is the best templating engine. In the humble opinion of the author – which is the law in this document – all the alternative templating engines written for PHP exist primarily to solve these two problems:

1. Separate *business* and *presentation* logic.
2. Keep the template code [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

Thus, if you are aware of these problems, you can solve them through best practices alone.

#### Separating business from presentation logic

Don't shoot yourself in the foot: always execute queries, pre-calculate values and check/set state at the top of your script AND THAN use those values to output your HTML code.


```php
<?php

// ==================
// = Business logic =
// ==================

// Process input
$filters = array(
    'offset' => !empty($_GET['offset']) ? (int) $_GET['offset'] : 0,
    'total' => !empty($_GET['total']) ? (int) $_GET['total'] : 100
);

// Execute business logic
$results = get_results($filters);

// ======================
// = Presentation logic =
// ======================
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

In MVC speak your controller is at the top, and your view is at the bottom. In one file.

You are welcome.

#### Keeping you template code DRY

æ comes with two ways to keep you template code DRY:

1. Container: for when several scripts are contained within the same template. Think: standard header + various content + standard footer.
2. Snippet: for when several scripts are presenting similar looking data. Think: article listings, user profiles, etc.

<!--
    TODO: Provide examples of containers and snippet use.
-->


### *Imperative* vs *declarative* programming

æ is biased towards imperative style of programming:

```php
// Setting options
ae::options('app')
    ->set('name', 'Application')
    ->set('description', 'Blah-blah-blah...');

// Generating response
$response = ae::response('text');

echo "Hello World";

$response
    ->cache(\ae\ResponseCache::year)
    ->dispatch();
```

In general all calls to æ follow these two patterns: 

1. Transformation: `ae::noun()->verb()->...->verb()` 
2. Invocation: `$noun = ae::noun()->noun()`.

There are exceptions, of course, like the query builder:

```php
$results = ae::database()
    ->select('*')
    ->from(table)
    ->order_by('column ASC')
    ->make();
```


### Request routing

### Response

## Library reference


### Core

* * *

### Path

### File

### Directory

* * *

### Buffer

### View

### Container

* * *

### Request

### Response

### Cache

* * *

### Image

### Form

* * *

### Database


<!-- Generated by \ae\Documentation on 22 December 2014 22:39:10 -->
