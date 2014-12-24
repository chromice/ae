
# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, and database operations.

This project has been created and maintained by its sole author to explore, validate and express his views on web development. As a result, this is an opinionated codebase that adheres to a few basic principles:

- **Simplicity:** There are no controllers, event emitters and responders, filters, template engines, etc. There is no config file to tinker with. All libraries come with their configuration options set to reasonable defaults.
- **Reliability**: All examples in this documentation are tested and their output verified. [Documentation](index.php) is the spec, [examples](../documentation) are unit tests. The syntax is designed to be expressive and error-resistant. 
- **Performance:** Libraries are loaded when you need them and there is no hidden layer above your code to worry about. Cached responses are served by Apache alone, i.e. without PHP overhead.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guide or standard. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you are just looking for a simple PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com). 

æ will be perfect for you, if your definition of a web application falls along these lines:

> **Opinion:** A web application is a bunch of scripts thrown together to concatenate a string of HTML in response to a string of HTTP.

In other words, if you are putting together a site with a bunch of forms that save data to a database, æ comes with everything you need.

You may still find it useful, even if you are thinking of web app architecture in terms of dispatchers, controllers, events, filters, etc. The author assumes you are working on something complex and wishes you a hearty good luck.

* * *

**Tests passed**: 24/27  (88.89%)

* * *

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Manual installation](#manual-installation)
    - [Configuring Composer](#configuring-composer)
    - [Hello world](#hello-world)
- [Design principles](#design-principles)
    - [Imperative and expressive syntax](#imperative-and-expressive-syntax)
    - [Exception safety](#exception-safety)
    - [Everything is a script](#Everything-is-a-script)
        - [Separate different kinds of logic](#separate-different-kinds-of-logic)
        - [Break your app into components](#break-your-app-into-components)
        - [Keep you template code DRY](#keep-you-template-code-dry)
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

You can download the latest release manually, drop it into your project and include <samp>ae/core.php</samp>:

```php
require 'path/to/ae/core.php';
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

include 'path/to/ae/core.php';

use \ae\Core as ae;

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

Buffer, container and response libraries all start capturing output on `__construct()` and process it on `__destruct()`. File library is using  `__destruct()` to unlock previously locked files and close their handles. Database library exposes a transaction object that rolls back any uncommited queries in `__destruct()`.

> **Opinion:** Generally speaking, all resources your object has allocated must be deallocated in the destructor. And if you find yourself cleaning state after catching an exception, you are doing it wrong.


### Everything is a script

> All the world's a stage,   
> And all the men and women merely players.

Strictly speaking æ is not a framework, because it imposes no rules on how your should structure your code: there are no canonical directory structure or file-naming conventions. As far as the author is concerned, all your code may be happily contained in a single <samp>index.php</samp> or be equally happily spread across dozens of directories and hundreds of files.

It would not be unreasonable to assume that it will be one or more PHP scripts that will be responsible for one or more of the following tasks:

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

include 'path/to/ae/core.php'

use \ae\Core as ae;

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




#### Keep you template code DRY

æ takes advantage of the fact that PHP itself is a powerful template engine and exposes two classes of objects to help you keep your presentation code [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself):

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

**NB!** The container object is assigned to `$container` variable. The object will persists while the script is being executed, allowing container to capture the content. The container script is always executed *after* the contained script.


## Library reference

To be done.

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



<!-- Generated on 24 December 2014 12:13:45 -->