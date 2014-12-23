
# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, and database operations.

This project has been created and maintained by its sole author to explore, validate and express his views on web development. As a result, this is an opinionated codebase that adheres to a few basic principles:

- **Simplicity:** æ abhors complexity. There are no controllers, event emitters and responders, filters, templating engines, etc. There is no config file to tinker with. All libraries come with their configuration options set to reasonable defaults.
- **Reliability**: All examples in this documentation are tested and their output verified. [Documentation](index.php) is the spec, [examples](../documentation) are unit tests. The syntax is designed to be expressive and error-resistant.
- **Performance:** Libraries are loaded when you need them and there is no hidden layer above your code to worry about. Cached responses are served by Apache alone, i.e. without PHP overhead.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guide or standard. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you are just looking for a simple PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com). 

* * *

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Manual installation](#manual-installation)
    - [Configuring Composer](#configuring-composer)
    - [Hello world](#hello-world)
- [Design principles](#design-principles)
    - [Exception safety](#exception-safety)
    - [PHP is the templating engine](#php-is-the-templating-engine)
        - [Separating business from presentation logic](#separating-business-from-presentation-logic)
        - [Keeping you template code DRY](#keeping-you-template-code-dry)
    - [Imperative and expressive syntax](#imperative-and-expressive-syntax)
- [Library reference](#library-reference)

* * *

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


## Design principles

æ was designed with the following principles in mind:

- Exceptions are awesome.
- [MVC](http://en.wikipedia.org/wiki/Model–view–controller) is a bad fit for the web.
- Imperative and expressive trumps declarative and formulaic.

As a result, æ *does not* do anything *magically*: it only does what explicitly told. It fails fast and loudly by throwing exceptions, when it is in recoverable state, and triggering errors and warnings, when something is definitely not right.

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

Generally speaking, all resources your object has allocated must be deallocated in the destructor. First of all, you should not rely on user remembering to close file handles or unlock locked files, dispatch requests or flush buffers, or do some other *implied* tasks. Secondly, if you find yourself cleaning up state after catching an exception, you are doing it wrong.


### PHP is the templating engine

æ takes advantage of the fact that PHP itself is a powerful templating engine. In the humble opinion of the author all the templating engines written for PHP exist primarily to:

1. Separate *business* and *presentation* logic.
2. Keep the template code [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).

And you can solve these problems through best practices alone.

#### Separating business from presentation logic

This one is simple: process input first, execute database queries, pre-calculate values and check/set state at the top of your script AND THAN use those values to generate HTML.


```php
<?php

// ===============
// = Input logic =
// ===============
$filters = array(
    'offset' => !empty($_GET['offset']) ? (int) $_GET['offset'] : 0,
    'total' => !empty($_GET['total']) ? (int) $_GET['total'] : 100
);
$filters = array_map($filters, function ($value) {
	return max($value, 0);
});


// ==================
// = Business logic =
// ==================
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

In MVC-speak your controller is at the top, and your view is at the bottom. In one file.

You are welcome.

#### Keeping you template code DRY

æ comes with two ways to keep you template code DRY:

1. **Snippet**: for when several scripts are presenting similar looking data. Think: article listings, user profiles, etc.
2. **Container**: for when several scripts are contained within the same template. Think: standard header + various content + standard footer.


##### Snippets

A snippet is a parameterized template, used to present snippets of information in a standardized form.


```php
<?php

use \ae\Core as ae;

$dictionary = array(
    'Ash' => 'a tree with compound leaves, winged fruits, and hard pale timber, widely distributed throughout north temperate regions.',
    'Framework' => 'an essential supporting structure of a building, vehicle, or object.',
    'PHP' => 'a server-side scripting language designed for web development but also used as a general-purpose programming language.'
);

ae::output('path/to/snippet.php', array(
    'data' => $dictionary
));
```

Provided `snippet.php` contains:


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

The script will render:


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

use \ae\Core as ae;

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

**NB!** We assigned container object to `$container` variable. The object will persists while the script is being executed, allowing container to capture the content. The container script is always executed *after* the contained script.


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

$queries = ae::database()
    ->select('*')
    ->from('table')
    ->order_by('column ASC')
    ->make();

```



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


<!-- Generated by \ae\Documentation on 23 December 2014 12:21:04 -->
