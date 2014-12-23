<?php

use \ae\Core as ae;

$doc = ae::documentation(__DIR__, '/', 'index.md');

?>

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

Rediness: {stats-percentage}
Examples failed: {stats-failed}/{stats-count}
Expectations failed: {stats-unexpected}/{stats-expected}

* * *

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Manual installation](#manual-installation)
    - [Configuring Composer](#configuring-composer)
    - [Hello world](#hello-world)
- [Design principles](#design-principles)
    - [Imperative and expressive syntax](#imperative-and-expressive-syntax)
    - [Exception safety](#exception-safety)
    - [Everyting is a script](#everyting-is-a-script)
        - [Break your app into components](#break-your-app-into-components)
        - [Keep you template code DRY](#keep-you-template-code-dry)
        - [Separate business from presentation logic](#Separate-business-from-presentation-logic)
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

You can download the latest release manually, drop it into your project and include <kbd>ae/core.php</kbd>:

```php
require 'path/to/ae/core.php';
```

### Configuring Composer

If you are using [Composer](https://getcomposer.org), make sure your <kbd>composer.json</kbd> references this repository AND has æ added as a requirement:

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

Let's create the most basic web application. Put this code into <kbd>index.php</kbd> in the web root directory:

<?= $example = $doc->example('/001_Hello_world') ?>

You should also instruct Apache to redirect all unresolved URIs to <kbd>index.php</kbd>, by adding the following rules to <kbd>.htaccess</kbd> file:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Now, if you open our app – located at, say, <kbd>http://localhost/</kbd> – in a browser you should see this:

<?= $example->expect('world.txt') ?>

If you change the address to <kbd>http://localhost/universe</kbd>, you should see:

<?= $example->on('/universe')->expect('universe.txt') ?>


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

<?php $syntax = $doc->example('006_Syntax') ?>

Most methods are chainable, including all setters: 

<?= $syntax->source('options.php') ?>

Some libraries operate on the buffered output, and don't have a corresponding setter at all:

<?= $syntax->source('response.php') ?>

Most of æ code follows these two patterns: 

1. Transformation: `ae::noun()->verb()->...->verb()` 
2. Invocation: `$noun = ae::noun()->...->noun()`.

There are exceptions, of course, like the query builder:

<?= $syntax->source('database.php') ?>


### Exception safety

In order to make your code exception safe, you must be aware the object life cycle.

`__construct()` method is called whenever a new object is instantiated. If the object is assigned to a variable, it will persist until either:

1. the variable is `unset()`
2. the scope where the variable was declared is destroyed, either naturally or *when an exception is thrown*
3. execution of the program halts

`__destruct()` method is called when there are no more variables pointing to the object.

Many æ libraries take advantage of the object life cycle. For instance, internal `\ae\ValueSwitch` class is using it to switch values temporarily:

<?= $switch_example = $doc->example('002_ValueSwitch'); ?>

Which will output:

<?= $switch_example->expect('output.txt') ?>

> **Opinion:** Generally speaking, all resources your object has allocated must be deallocated in the destructor. First of all, you should not rely on user remembering to close file handles or unlock locked files, dispatch requests or flush buffers, or do some other *implied* tasks. Secondly, if you find yourself cleaning up state after catching an exception, you are doing it wrong.


### Everyting is a script

> All the world's a stage,   
> And all the men and women merely players.

Strictly speaking æ is not a framework, because it imposes no rules on how your should structure your code: there are no canonical directory structure or file-naming conventions. As far as the author is concerned, all your code may be happily contained in a single <kbd>index.php</kbd> or be equally happily spread across dozens of directories and hundreds of files.

It would not be unreasonable to assume that it will be one or more PHP scripts that will be responsible for one or more of the following tasks:

- *Handling a request*, i.e. determine what to do based on request URI, GET/POST parameters and form values.
- *Changing internal state*, e.g. cookies, session variables, records in the database.
- *Generating a response*, i.e. spitting out a string of HTML code.

The author does not want to be unfairly prescriptive, but here are a few pieces of advice that you may find helpful:

#### Break your app into components

æ lets you either delegate the request to a directory or a file, or process it in anonymous callback function. Typically the first (few) segment(s) should determine the script that should handle the request, while the remainder of the segments further qualify what kind of request it is and specify its parameters.

For example, you may want to handle user authentication and let:

- <kbd>/account/login</kbd> – Authentication form.
- <kbd>/account/logout</kbd> – Log user out.
- <kbd>/account/edit</kbd> – Edit account information.
- <kbd>/account</kbd> – Display account information.

You may also have a script to display products:

- <kbd>/products[/page/{page-number}]</kbd> – Display (paginated) list of products.
- <kbd>/products/{product-id}/{product-seo-title}</kbd> – Display a product page.

And finally, you may want to display some pages:

- <kbd>/</kbd> – Display home page.
- <kbd>/about-us</kbd> – Display about us page.
- <kbd>/about-us/team</kbd> – List team members.

Now, here's what an <kbd>index.php</kbd> in the web root directory may look like:

<?= $routing = $doc->example('007_Routing'); ?>


<?php $routing->on('/account/login')->outputs('Authentication form.') ?>
<?php $routing->on('/account/logout')->outputs('Log user out.') ?>
<?php $routing->on('/account/edit')->outputs('Edit account information.') ?>
<?php $routing->on('/account')->outputs('Display account information.') ?>


<?php $routing->on('/products')->outputs('List product page #1.') ?>
<?php $routing->on('/products/page/33')->outputs('List product page #33.') ?>
<?php $routing->on('/products/123')->outputs('Display product #123.') ?>

<?php $routing->on('/')->outputs('Display home page.') ?>
<?php $routing->on('/about-us')->outputs('Display about us page.') ?>
<?php $routing->on('/about-us/team')->outputs('List team members.') ?>
<?php $routing->on('/unknown-page')->outputs('No page found.') ?>

#### Keep you template code DRY

æ takes advantage of the fact that PHP itself is a powerful template engine and exposes two classes of objects to help you keep your presentation code DRY:

1. **Snippet**: for when several scripts are presenting similar looking data. Think: article listings, user profiles, etc.
2. **Container**: for when several scripts are contained within the same template. Think: standard header + various content + standard footer.

##### Snippets

A snippet is a parameterized template, used to present snippets of information in a standardized form.

<?= $snippet = $doc->example('/004_Template_snippet'); ?>

Provided <kbd>snippet.php</kbd> contains:

<?= $snippet->source('snippet.php') ?>

The script will render:

<?= $snippet->expect('output.html') ?>

##### Containers

A container is a parameterized template, used as a wrapper. Here's an example of a container:

<?php $container = $doc->example('/005_Template_container'); ?>

<?= $container->source('container.php') ?>

Another script can use it:

<?= $container->source('index.php') ?>

Which will result in:

<?= $container->expect('output.html') ?>

**NB!** The container object is assigned to `$container` variable. The object will persists while the script is being executed, allowing container to capture the content. The container script is always executed *after* the contained script.

#### Separate business and presentation logic

Process input first, execute database queries, pre-calculate values and check/set state at the top of your script AND THAN use those values to generate HTML.

<?= $doc->example('/003_Logic_separation') ?>

In MVC-speak your controller is at the top, and your view is at the bottom. Just in one file.


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


