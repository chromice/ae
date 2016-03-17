# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, database operations, and easy debugging and profiling.

This project has been created by its sole author to explore, express and validate his views on web development. As a result, this is an opinionated codebase that attempts to achieve the following goals:

- **Simplicity:** There are no controllers, event emitters and responders, filters, template engines. There are no config files to tinker with, either: all libraries come preconfigured with sensible default values.
- **Reliability**: The APIs were designed to be expressive and user error-resistant. Versions of this code have powered a few moderately complex websites and applications, which helped iron most of the kinks out.
- **Performance:** All libraries have been designed with performance and efficiency in mind. Responses can be cached statically and served by Apache alone.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guide or standard. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you just need a lean PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com).

æ will be perfect for you, if your definition of a web application falls along these lines:

> A web application is a bunch of scripts thrown together to concatenate a string of text (HTTP response) in response to another string of text (HTTP request).

In other words, æ will not let you forget that most of the back-end programming is a glorified string manipulation, but it will alleviate the most cumbersome aspects of it. 

In more practical terms, if you are putting together a site with some forms that save data to a database, and then present that data back to the user on a bunch of pages, æ comes with everything you need.

You may still find it useful, even if you are thinking of web app architecture in terms of dispatchers, controllers, events, filters, etc. The author assumes you are working on something complex and wishes you a hearty good luck. ;-)

* * *

## Table of contents

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Manual installation](#manual-installation)
    - [Configuring Composer](#configuring-composer)
    - [Hello world](#hello-world)
- [Request](#request)
    - [Request mapping](#request-mapping)
- [Response](#response)
    - [Buffer](#buffer)
    - [Template](#template)
    - [Layout](#layout)
    - [Cache](#cache)
- [File](#file)
    - [Handling uploaded files](#handling-uploaded-files)
    - [Passing metadata](#passing-metadata)
    - [File size constants](#file-size-constants)
- [Image](#image)
    - [Resizing and cropping](#resizing-and-cropping)
    - [Applying filters](#applying-filters)
    - [Conversion and saving](#conversion-and-saving)
- [Form](#form)
    - [Declaration](#declaration)
    - [Validation](#validation)
    - [Presentation](#presentation)
    - [Complex field types](#complex-field-types)
- [Database](#database)
    - [Making queries](#making-queries)
    - [Query functions](#query-functions)
    - [Active record](#active-record)
    - [Transactions](#transactions)
- [Inspector](#inspector)
    - [Debugging](#debugging)
    - [Profiling](#profiling)
- [Utilities](#utilities)
    - [File system paths](#file-system-paths)
    - [Exception safety](#exception-safety)
    - [Configuration options](#configuration-options)
    - [Unit testing](#unit-testing)
- [License](#license)

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

You can download the latest release manually, drop it into your project and `require` <samp>ae/core.php</samp>:

```php
require 'path/to/ae/core.php';
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

Let's create the most basic of web applications. Put this code into <samp>index.php</samp> in the web root directory:

```php
require 'path/to/ae/core.php';

$path = \ae\request\path();

echo 'Hello ' . ( isset($path[0]) ? $path[0] : 'world' ) . '!';
```

Now let's also instruct Apache to redirect all unresolved URIs to <samp>index.php</samp>, by adding the following rules to <samp>.htaccess</samp> file:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Now, if you open our app (located at, say, <samp>http://localhost/</samp>) in a browser you should see this:

```txt
Hello world!
```

If you change the address to <samp>http://localhost/universe</samp>, you should see:

```txt
Hello universe!
```


## Request

Request library is a lightweight abstraction of HTTP requests.

You can distinguish between different kinds of requests using `\ae\request\method()` function:

```php
if (\ae\request\method() === \ae\request\GET)
{
    echo "<p>This is a GET request.</p>";
}
else if (\ae\request\method() === \ae\request\POST)
{
    echo "<p>This is a POST request.</p>";
}
```

You can access URI path segments using `\ae\request\path()` function:

```php
// GET /some/arbitrary/script.php HTTP/1.1

$path = \ae\request\path();

echo $path[0]; // some
echo $path[1]; // arbitrary
echo $path[2]; // script.php

echo $path; // some/arbitrary/script.php
```

All requests have a type (<samp>html</samp> by default), which is determined by the *extension* part of the URI path.

```php
// GET /some/arbitrary/request.json HTTP/1.1

echo \ae\request\type(); // json
```

To get the client IP address, you should use `\ae\request\address()` function. If your app is running behind a reverse-proxy and/or load balancer, you must specify their IP addresses first:

```php
\ae\request\configure('proxies', ['83.14.1.1', '83.14.1.2']);

$client_ip = \ae\request\address();
```

You can use `\ae\request\query()` and `\ae\request\data()` functions to access `$_GET` and `$_POST` arrays:

```php
$get = \ae\request\query(); // returns $_GET
$post = \ae\request\data(); // returns $_POST

$action = \ae\request\query('action', 'search'); // returns $_GET['action'] or 'search'
$term = \ae\request\data('term'); // returns $_POST['term'] or NULL
```

You can access uploaded files (when request body is encoded as <samp>multipart/form-data</samp>), using `\ae\request\files()` function.

```php
$files = \ae\request\files();
// returns an associative array of uploaded files:
// e.g. ['form_field_name' => \ae\file(), ...]
```

If you need to access raw request body, use `\ae\request\body()` function:

```php
$post = \ae\request\body(); // same as file_get_contents("php://input")
```


### Request mapping

You should always strive to break down your application into smallest independent components. The best way to handle a request is to map it to a specific function or template that encapsulates a part of your application's functionality.

Requests are mapped using rules, which are key-value pairs of path pattern, and either an object that conforms to `\ae\response\Dispatchable` interface or a function that returns such an object.

Here's an example of a request being mapped to page templates:

```php
// GET /about-us HTTP/1.1

\ae\request\map([
    // ...
    '/about-us' => \ae\template('path/to/about-us-page.php'),
    '/our-work' => \ae\template('path/to/our-work-page.php'),
    // ...
]);
```

Or we can write a more generic rule that handles all root level pages by using a placeholder, and mapping it to a function:

```php
// GET /about-us HTTP/1.1

\ae\request\map([
    // ...
    '/{any}' => function ($slug) {
        return \ae\template('path/to/' . $slug . '-page.php');
        // returns '/path/to/about-us-page.php' template, it it exists
    },
    // ...
]);
```

As you can see, we used `{any}` placeholder to catch the slug of the page and pass its value to our handler function as first argument.

> `{any}` placeholder can match (and capture) a substring only within one path segment, i.e. it can match any character other than <smap>/</smap> (forward slash).

If the template file does not exist, `\ae\template()` will throw an `\ae\path\Exception`, which in turn will result in `\ae\response\error(404)` being dispatched.

> If a request handler throws `\ae\path\Exception`, `\ae\response\error(404)` will be dispatched. If it throws any other exception, `\ae\response\error(500)` will be dispatched instead.

Now, let's assume we want users to be able to download files from a specific directory:

```php
// GET /download/directory/document.pdf HTTP/1.1

\ae\request\map([
    // ...
    '/download' => function ($file_path) {
        return \ae\file('path/to/downloadable/files/' . $file_path)
        // returns '/path/to/downloadable/files/directory/document.pdf' file, if it exists
            ->download(true);
    },
    // ...
]);
```

First of all, we will take advantage of the fact that `\ae\file()` function returns an object that conforms to `\ae\response\Dispatchable` interface. Secondly, whenever actual matched URI path is longer than the pattern, the remainder of it is passed as *the last argument* to our handler. And thirdly, we use `download()` method to set <samp>Content-Disposition</samp> header to <samp>attachment</samp>, and force the download rather than simply display the content of the file.

> You can pass a custom file to `download()` method, if you do not want to use the actual file name.

Image processing is a very common problem that can be solved in multiple ways. Let's create a simple image processor that can take any image, resize it to predefined dimensions, and cache the result for 10 years:

```php
// GET /resized/square/avatars/photo.jpg HTTP/1.1

\ae\request\map([
    // ...
    '/resized/{alpha}' => function ($format, $path) {
        switch ($format)
        {
            case 'square':
                $width = 256;
                $height = 256;
                break;
            
            case 'thumbnail':
                $width = 400;
                $height = 600;
                break;
            
            default:
                return \ae\request\error(404);
        }
        
        return \ae\image('image/directory/'. $path)
            ->fill($width, $height)
            ->cache(10 * \ae\cache\year, \ae\cache\server_side);
    },
    // ...
]);
```

Similarly to the file download example, the file path is passed as *the last argument* to our handler. In addition to that, we catch the image format as *the first argument*. The object returned by `\ae\image()` conforms to `\ae\response\Cachable` interface (in addition to `\ae\response\Dispatchable`) and implements `cache()` method.

And finally, our last rule will display home page, *or* show 404 error for all unmatched requests by returning `null`:

```php
// GET / HTTP/1.1

\ae\request\map([
    // ...
    '/' => function ($path) {
        return empty($path) ? \ae\template('path/to/home-page.php') : null;
    }
]);
```

> All rules are processed in sequence. You should always put rules with higher specificity at the top. <samp>'/'</samp> is the least specific rule and will match *any* request.


## Response

Response library is a set of functions, classes, and interfaces that lets you create a response object, set its content and headers, and (optionally) cache and compress it. It is designed to work with `\ae\request\map()` function (see above), which expects you to create a response object for each request.

> Objects returned by `\ae\response()`, `\ae\buffer()`, `\ae\template()`, `\ae\file()`, `\ae\image()` implement `\ae\response\Dispatchable` interface, which allows you to dispatch them. You should refrain from using `dispatch()` method yourself though, and use the request mapping pattern as much as possible.

Here is an example of a simple application that creates a response, sets one custom header, caches it for 5 minutes, and dispatches it. The response is also automatically compressed using Apache's `mod_deflate`:

```php
<?php 
// GET /hello-world HTTP/1.1

\ae\response\configure('compress', true);

$response = \ae\response()
    ->header('X-Header-Example', 'Some value');

?>
<h1>Hello world</h1>
<?php 

$response
    ->cache(5 * \ae\cache\minute, \ae\cache\server_side)
    ->dispatch('hello-world.html');

?>
```

When response object is created, it starts buffering all output. Once the `dispatch()` method is called, the buffering stops, HTTP headers are set, and content is output.

> You must explicitly specify the response path when using `dispatch()` method. To create a response for the current request use `\ae\request\path()` function.

By default all responses are <samp>text/html</samp>, but you can change the type by either setting <samp>Content-type</samp> header to a valid mime-type or appending an appropriate file extension to the dispatched path, e.g. <samp>.html</samp>, <samp>.css</samp>, <samp>.js</samp>, <samp>.json</samp> 


### Buffer

You can create a buffer and assign it to a variable to start capturing output. All output is captured until the instance is destroyed:

<!--
     or buffered content is used ???
-->

```php
$buffer = \ae\buffer();

echo "I'm buffered!";

$content = (string) $buffer; // $content === "I'm buffered!"

echo "I'm still buffered!";

unset($buffer);

echo "And I'm not buffered!";
```

> `\ae\buffer()` returns an instance of `\ae\Buffer` class, which implements `__toString()` magic method that always returns a string currently contained in the buffer.


### Template

Use `\ae\template()` to capture output of a parameterized script:

```php
$output = (string) \ae\template('your/page.php', [
    'title' => 'Example!',
    'body' => '<h1>Hello world!</h1>'
]);
```

Provided the content of <samp>your/page.php</samp> is...

```php
<title><?= $title ?></title>
<body><?= $body ?></body>
```

...the `$output` variable will contain:

```html
<title>Example!</title>
<body><h1>Hello world!</h1></body>
```

> `\ae\template()` returns an instance of `\ae\Template` class, which implements `__toString()` magic method that renders the template with specified parameters.


### Layout

Layout library allows you to wrap output of a script with output of another script. The layout script is executed *last* , thus avoiding many problems of using separate header and footer scripts to keep the template code [DRY](http://en.wikipedia.org/wiki/DRY).

Here's an example of HTML body container <samp>layout_html.php</samp>:

```html
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <?= $__content__ ?>
</body>
</html>
```

Another script <samp>hello_world.php</samp> can use it like this:

```php
<?php $layout = \ae\layout('path/to/layout_html.php', [
    'title' => 'Layout example'
]); ?>
<h1>Hello World!</h1>
```

When rendered, it will produce this:

```html
<html>
<head>
    <title>Layout example</title>
</head>
<body>
    <h1>Hello World!</h1>
</body>
</html>
```

### Cache

If you want your response to be cached client-side for a number of minutes, you should use `cache()` method of the response object. It will set <samp>Cache-Control</samp>, <samp>Last-Modified</samp>, and <samp>Expires</samp> headers for you. If the response is public (i.e. you passed `\ae\cache\server_side` to `cache()` method as the second argument), it will save the response server-side as well.

> Objects returned by `\ae\response()`, `\ae\buffer()`, `\ae\template()`, `\ae\file()`, `\ae\image()` implement `\ae\response\Cacheable` interface, which allows you to cache them.

#### Configuration

The responses are saved to <samp>cache</samp> directory (in the *web root* directory) by default. For caching to work correctly this directory must exist and be writable. You must also configure Apache to look for cached responses in that directory:

1. Put the following rules into <samp>.htaccess</samp> file in the *web root* directory:

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

2. And here are the rules that <samp>cache/.htaccess</samp> must contain:

    ```apache
    <IfModule mod_rewrite.c>
        RewriteEngine on

        # If no matching file found, redirect back to index.php
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*) /index.php?/$1 [L,QSA]
    </IfModule>
    ```

With everything in place, Apache will first look for an unexpired cached response, and only if it finds nothing, will it route the request to <samp>/index.php</samp>.

#### Cache functions

You can save any response manually using `\ae\cache\save()` function:

```php
\ae\cache\save('hello-world.html', $response, 2 * \ae\cache\hour);
```

You can delete any cached response using `\ae\cache\delete()` function by passing full or partial URL to it:

```php
\ae\cache\delete('hello-world.html');
```

It may be a good idea to periodically remove all *stale* cache entries via `\ae\cache\clean()`:

```php
\ae\cache\clean();
```

> Garbage collection is a resource-intensive operation, so its usage should be restricted to a cron job.

To completely erase all cached data use `\ae\cache\purge()` function:

```php
\ae\cache\purge();
```


## File

File library is a wrapper for standard file functions: `fopen()`, `fclose()`, `fread()`, `fwrite()`, `copy()`, `rename()`, `is_uploaded_file()`, `move_uploaded_file()`, etc. All methods throw `\ae\file\Exception` on error.

```php
$file = \ae\file('path/to/file.ext')
    ->open('w+')
    ->lock()
    ->truncate()
    ->write('Hello World');
    
$file->seek(0);

if ($file->tell() === 0)
{
    echo $file->read($file->size());
}

// Unlock file and close its handle
unset($file);
``` 

You can access basic information about the file:

```php
$file = \ae\file('path/to/file.txt');

echo $file->size(); // 12
echo $file->name(); // file.txt
echo $file->type(); // txt
echo $file->mime(); // text/plain

$path = $file->path(); // $path = \ae\path('path/to/file.txt')
```

Existing files can be copied, moved, and deleted:

```php
$file = \ae\file('path/to/file.txt');

if ($file->exists())
{
    $copy = $file->copy('./file-copy.txt');
    $file->delete();
    $copy->move('./file.txt');
}
```

### Handling uploaded files

The library can handle uploaded files as well:

```php
$file = \ae\file($_FILES['file']['tmp_name']);

if ($file->is_uploaded())
{
    $file->move('path/to/destination/' . $_FILES['file']['name']);
}
```

### Passing metadata

You can assign arbitrary metadata to a file, e.g. database keys, related files, alternative names, etc.:

```php
$file['real_name'] = 'My text file (1).txt';
$file['resource_id'] = 123;

foreach ($file as $meta_name => $meta_value)
{
    echo "{$meta_name}: $meta_value\n";
}
```

Metadata is transient and is never saved to disk, but it may be used by different parts of your application to communicate additional information about the file, when you pass an object representing it around.

### File size constants

File library defines several constants that you are encouraged to use when dealing with file sizes:

```php
echo \ae\file\byte;     // 1
echo \ae\file\kilobyte; // 1000
echo \ae\file\kibibyte; // 1024
echo \ae\file\megabyte; // 1000000
echo \ae\file\mebibyte; // 1048576
echo \ae\file\gigabyte; // 1000000000
echo \ae\file\gibibyte; // 1073741824
echo \ae\file\terabyte; // 1000000000000
echo \ae\file\tebibyte; // 1099511627776
echo \ae\file\petabyte; // 1000000000000000
echo \ae\file\pebibyte; // 1125899906842624
```


## Image

Image library is a wrapper around standard GD library functions.

You can retrieve basic information about the image:

```php
$image = \ae\image('example/image_320x240.jpg');

echo $image->width();  // 320
echo $image->height(); // 240
echo $image->type();   // jpeg
echo $image->mime();   // image/jpeg
```

<!--
    TODO: File specific information?
    TODO: Copying, moving, deleting images?
-->

### Resizing and cropping

You can transform the image by changing its dimensions in 4 different ways:

- `scale($width, $height)` scales the image in one or both dimensions; use `null` for either dimension to scale proportionally.
- `crop($width, $height)` crops the image to specified dimensions; if the image is smaller in either dimension, the difference will be padded with transparent pixels.
- `fit($width, $height)` scales the image, so it fits into a box defined by target dimensions.
- `fill($width, $height)` scales and crops the image, so it completely covers a box defined by target dimensions.

You will rarely need to use the first two methods, as the latter two cover most of use cases:

```php
$photo = \ae\image('path/to/photo.jpg');

$big = $photo
    ->fit(1600, 1600)
    ->suffix('_big')
    ->save();

$small = $big
    ->fit(640, 640)
    ->suffix('_small')
    ->save();

$thumbnail = $small
    ->fill(320, 320)
    ->suffix('_thumbnail')
    ->save();
```

You can specify the point of origin using `align()` method, before you apply `crop()` and `fill()` transformations to an image:

```php
$thumbnail = $small
    ->align(\ae\image\left, \ae\image\top)
    ->fill(320, 320)
    ->suffix('_thumbnail')
    ->save();
```

This way you can crop a specific region of the image. The `align()` method requires two arguments:

1. Horizontal alignment:
    - a number from <samp>0</samp> (left) to <samp>1</samp> (right); <samp>0.5</samp> being the center
    - a constant: `\ae\image\left`, `\ae\image\center`, or `\ae\image\right`
2. Vertical alignment:
    - a number from <samp>0</samp> (top) to <samp>1</samp> (bottom); <samp>0.5</samp> being the middle
    - a constant: `\ae\image\top`, `\ae\image\middle`, or `\ae\image\bottom`

By default, the origin point is in the middle of both axes.

### Applying filters

You can also apply one or more filters:

```php
$thumbnail
    ->blur()
    ->colorize(0.75, 0, 0)
    ->save();
```

Here are all the filters available:

- `blur()` blurs the image using the Gaussian method.
- `brightness($value)` changes the brightness of the image; accepts a number from <samp>-1.0</samp> to <samp>1.0</samp>.
- `contast($value)` changes the contrast of the image; accepts a number from <samp>-1.0</samp> to <samp>1.0</samp>.
- `colorize($red, $green, $blue)` changes the contrast of the image; accepts numbers from <samp>0.0</samp> to <samp>1.0</samp>.
- `grayscale()` converts the image into grayscale.
- `negate()` reverses all colors of the image.
- `pixelate($size)` applies pixelation effect to the image.
- `smooth($value)` makes the image smoother; accepts integer values, <samp>-8</samp> to <samp>8</samp> being the sweat spot.

### Conversion and saving

By default when you use `save()` method the image type and name is preserved.

If you want to preserve association with the original image, you can append or prepend a string to its name using `siffix()` and `prefix()` methods respectively: 

```php
\ae\image('some/photo.jpg')
    ->prefix('unknown_')
    ->suffix('graphic_image')
    ->save(); // some/unknown_photographic_image.jpg
```

If you want to change the name *or* image type completely, you should provide file name to `save()` method:

```php
$image = \ae\image('some/image.png');

$image
    ->quality(75)
    ->progressive(true)
    ->save('image.jpg'); // some/image.jpg

$image
    ->interlaced(true)
    ->save('image.gif'); // some/image.gif
```


## Form

Form library lets you create web forms and validate them both on the client and the server sides using HTML5 constraints. Both forms and individual controls implement `__toString()` magic method and can render themselves into valid HTML when cast to string, but you can render them manually, if you so desire.


### Declaration

You can create a new form using `\ae\form()` function. It takes form name – it must be unique within the context of a single web page – as the first argument, and (optionally) an array of default field values as the second argument:

```php
$form = \ae\form('Profile', [
        'name' => 'John Connor',
    ]);
```

You can add individual form fields to the form:

```php
$form['name'] = \ae\form\text('Full name')
    ->required()
    ->max_length(100);

$form['email'] = \ae\form\email('Email address')
    ->required();

$form['phone'] = \ae\form\tel('Phone number'); 
// N.B. Never use \ae\form\number() for phone numbers!

$form['phone_type'] = \ae\form\radio('Is home/work/mobile number?', 'home')
    ->options([
        'home' => 'Home number',
        'work' => 'Work number',
        'mobile' => 'Mobile number',
    ])
    ->required();

$form['birth_date'] = \ae\form\date('Date of birth')
    ->max('-18 years', 'You must be at least 18 years old!');

$form['photos'] = \ae\form\file('Photos of you')
    ->accept('image/*')
    ->multiple()
    ->min_width(200)
    ->min_height(200)
    ->max_size(10 * \ae\file\megabyte);
```

You can assign an instance of any class extending `\ae\form\DataField` or `\ae\form\FileField` classes to a form. Once field is assigned, the form will use either `$_POST` or `$_FILES` array as its data source, depending on which parent class the object is related to.

Field objects have methods that you can use to set their validation constraints. Most of those constraints have/behave similar to their HTML5 counterparts. See [Constraints](#validation-constraints) section for more information.

#### Basic field types

Form library supports most kinds of `<input>`, `<select>`, or `<textarea>` fields out of the box. All field factory functions accept a field label as the first argument, and (optionally) a default value(s) as the second argument:

> You can specify default values when creating both form and field objects. However, default values of the form always override default values of its fields.

##### Text strings

- `\ae\form\text()` uses <samp>&lt;input type="text"&gt;</samp> control.
- `\ae\form\password()` uses <samp>&lt;input type="password"&gt;</samp> control.
- `\ae\form\search()` uses <samp>&lt;input type="search"&gt;</samp> control; all newline characters are removed automatically.
- `\ae\form\tel()` uses <samp>&lt;input type="tel"&gt;</samp> control.
- `\ae\form\url()` uses <samp>&lt;input type="url"&gt;</samp> control; has `pattern(\ae\form\url)` constraint applied.
- `\ae\form\email()` uses <samp>&lt;input type="email"&gt;</samp> control; will accept multiple values, if `multiple()` method is called; has `pattern(\ae\form\email)` constraint applied.

##### Number fields

- `\ae\form\integer()` uses <samp>&lt;input type="number"&gt;</samp> control; has `pattern(\ae\form\integer)` constraint applied.
- `\ae\form\decimal()` uses <samp>&lt;input type="number"&gt;</samp> control; has `pattern(\ae\form\decimal)` constraint applied.
- `\ae\form\range()` uses <samp>&lt;input type="range"&gt;</samp> control; has `pattern(\ae\form\decimal)` constraint applied; to match HTML5 spec, `min(0)` and `max(100)` constraints are applied to this field by default, and initial value is set to <samp>min+(max-min)/2</samp>.


##### Dates and time fields

- `\ae\form\date()` uses <samp>&lt;input type="date"&gt;</samp> control; has `pattern(\ae\form\date)` constraint applied.
- `\ae\form\month()` uses <samp>&lt;input type="month"&gt;</samp> control; has `pattern(\ae\form\month)` constraint applied.
- `\ae\form\week()` uses <samp>&lt;input type="week"&gt;</samp> control; has `pattern(\ae\form\week)` constraint applied.
- `\ae\form\time()` uses <samp>&lt;input type="time"&gt;</samp> control; has `pattern(\ae\form\time)` constraint applied.
- `\ae\form\datetime()` uses <samp>&lt;input type="datetime-local"&gt;</samp> control; has `pattern(\ae\form\datetime)` constraint applied.

##### Fields with options

- `\ae\form\select()` uses <samp>&lt;select&gt;</samp> control; will accept multiple values, if `multiple()` method is called.
- `\ae\form\radio()` uses multiple <samp>&lt;input type="radio"&gt;</samp> controls.
- `\ae\form\checkbox()` behaves differently depending on whether options are provided via `options()` method:
    - if no options are provided, uses a single <samp>&lt;input type="checkbox"&gt;</samp> control; its value is either `true` or `false`
    - if one or more options are provided, uses multiple <samp>&lt;input type="checkbox"&gt;</samp> controls to render them; its value is an array of checked options.

##### Miscellaneous

- `\ae\form\color()` uses <samp>&lt;input type="color"&gt;</samp> control; has `pattern(\ae\form\color)` constraint applied.
- `\ae\form\textarea()` uses <samp>&lt;textarea"&gt;</samp> control; mostly behaves like `\ae\form\text()` field, but does not have access to `pattern()` validation constraint.
- `\ae\form\file()` will accept multiple values, if `multiple()` method is called.


#### HTML attributes

All fields have `attributes()` method, which you can use to change control's attributes:

```php
$field = \ae\form\textarea('Description')
    ->attributes(['cols' => 40, 'rows' => 10]);
```



#### Multiple values

The following field types will accept and produce multiple values, if `multiple()` method is called:

- `\ae\form\email()`
- `\ae\form\file()`
- `\ae\form\select()`

> `\ae\form\checkbox()` does not have `multiple()` method, but the field will accept more than one value, if you provide multiple options via `options()` method.

#### Validation constraints

Most validation constraints are field type-specific, but all field types have access to the following constraints:

- `required()` – the field must contain a non-empty a value; corresponds to <samp>required</samp> attribute in HTML5.
- `valid($function)` allows you to specify an arbitrary constraint using a *callable* `$function`; current field value is passed as the first argument, and reference to form is passed as the second argument; function must return either `true`, if the value is valid, or an error message:

```php
$field = \ae\form\text('First name')
    ->valid(function ($value, $form) {
        return $value === 'Anton' ? 'Sorry Anton, cannot let you through!' : true;
    });
```

##### Text field constraints

- `min_length($length)` and `max_length($length)` define maximum and minimum length constraints; they correspond to <samp>minlength</samp> and <samp>maxlength</samp> attributes in HTML5.
- `pattern($pattern)` defines a pattern constraint; `$pattern` must be a valid regular expression without slashes, e.g. `#[0-9a-f]{6}`; it corresponds to <samp>pattern</samp> attribute in HTML5; the library has several patterns defined as constants:
    - `\ae\form\integer` – an integer number, e.g. <samp>-1</samp>, <samp>0</samp>, <samp>1</samp>, <samp>2</samp>, <samp>999</samp>.
    - `\ae\form\decimal` – a decimal number, e.g. <samp>0.01</samp>, <samp>-.02</samp>, <samp>25.00</samp>, <samp>30</samp>.
    - `\ae\form\numeric` – a string consisting of numeric characters, e.g. <samp>123</samp>, <samp>000</samp>.
    - `\ae\form\alpha` – a string consisting of alphabetic characters, e.g. <samp>abc</samp>, <samp>cdef</samp>.
    - `\ae\form\alphanumeric` – a string consisting of both alphabetic and numeric characters, e.g. <samp>a0b0c0</samp>, <samp>0000</samp>, <samp>abcde</samp>.
    - `\ae\form\color` – a hexadecimal representation of a color, e.g. <samp>#fff000</samp>, <samp>#434343</samp>.
    - `\ae\form\time` – a valid time, e.g. <samp>14:00:00</samp>, <samp>23:59:59.99</samp>.
    - `\ae\form\date` – a valid date, e.g. <samp>2009-10-15</samp>.
    - `\ae\form\datetime` – a valid date and time, e.g. <samp>2009-10-15T14:00:00-9:00</samp>.
    - `\ae\form\month` – a valid month, e.g. <samp>2009-10</samp>.
    - `\ae\form\week` – a valid week, e.g. <samp>2009-W42</samp>.
    - `\ae\form\email` – a valid email address.
    - `\ae\form\url` – a valid URL.
    - `\ae\form\uk_postcode` – a valid UK postal code.


##### Number and date field constraints

- `min($value)` and `max($value)` define minimum and maximum value constraints; the correspond to <samp>min</samp>, <samp>max</samp> attributes in HTML5; date/time fields parse `$value` using `strtotime()` function.

##### File field constraints

- `accept($types)` defines a file type constraint; `$types` must be a comma-separated list of either file extensions (with full stop character), valid MIME types, or <samp>audio/\*</samp> or <samp>image/\*</samp> or <samp>video/\*</samp>; corresponds to <samp>accept</samp> attribute in HTML.
- `min_size($size)`, `max_size($size)` define file size constraints.
- `min_width($width)`, `max_width($width)`, `min_height($height)`, `max_height($height)`, `min_dimensions($width, $height)`, `max_dimensions($width, $height)` define image dimension constraints.

All validation constraints will generate human readable error messages automatically. If you wish to override the default error message, you can do so by passing your error message as *the last argument*.


### Validation

Once the form is declared, you can check if it has been submitted, and if the submitted values are valid:

```php
if ($form->is_submitted() && $form->is_valid())
{
    // All HTML5 constraints are met
    // Time for custom validation
    $is_valid = true;
    
    // Validate name
    if ($form['name']->value === 'John Connor')
    {
        $form['name']->error = 'You are not John Connor! State your real name please.';
        $is_valid = false;
    }
    
    // If name is valid, save the data
    if ($is_valid)
    {
        $user = Profile::find($user_id);
        
        foreach ($form as $name => $field)
        {
            $user->$name = $field->value;
        }
        
        $user->save();
        
        echo '<p class="message success">Successfully saved!</message>';
    }
}
```

When `is_valid()` method is called, the form will iterate all its fields, calling their `validate()` method. All validation constraints that were set when fields were declared are checked at this stage.


### Presentation

In order to render a form into HTML you can simply cast it to a string:

```php
echo (string) $form;
```

Alternatively, you can render the form by manually calling `open()` and `close()` methods to create `<form>` and `</form>` (and a few hidden) tags, and iterating all fields and rendering them individually:

```php
<?= $form->open(['novalidate' => true]) ?>

<?php foreach ($form as $field): ?>
    <div class="field <?= $field->classes ?>">
        <?= $field->label() ?>
        <?= $field->control(['placeholder' => 'Enter ' . $field->label]); ?>
        <?= $field->error() ?>
    </div>
<?php endforeach ?>

<?= $form->close() ?>
```

All basic fields expose the following properties that you can use to render them:

- `label` contains the field label, e.g. <samp>Name</samp>, <samp>Options</samp>, etc.
- `name` contains the field name, e.g. <samp>name</samp>, <samp>options[]</samp>, <samp>repeater[0][name]</samp>, etc.; set by the form object when the field is assigned to it.
- `error` contains an error message set during validation, e.g. <samp>Name is required.</samp>.
- `value` contains current value(s), either submitted or default.
- `classes` contains a string of HTML classes indicating the state of the field, e.g. <samp>text-field required-field</samp>.

The following method render individual components of a field:

- `label([$attributes])` renders field label, e.g. <samp>&lt;label for=&quot;field-id&quot;&gt;Field label&lt;/label&gt;</samp>; returns an empty string for `\ae\form\checkbox()` fields with no options.
- `control([$attributes])` renders field control, e.g. <samp>&lt;input type=&quot;text&quot; name=&quot;name&quot; value=&quot;&quot;&gt;</samp>.
- `error([$before = '<em class="error">', $after = '</em>'])` renders field error, if it has one.


### Complex field types

Complex fields use multiple basic fields to create a more specialized field:

- `\ae\form\fieldset()` acts as a container for several fields; it corresponds to  <samp>&lt;fieldset&gt;</samp> element in HTML.
- `\ae\form\compound()` allows you to combine multiple fields together to produce a single value, e.g. you could break name field into separate first, (optional) middle, and last name fields.
- `\ae\form\repeater()` is a repeating sequence of a predefined group of fields.
- `\ae\form\sequence()` is an arbitrary sequence of multiple predefined groups of fields.

#### Field set

In order to create a <samp>&lt;fieldset&gt;</samp>, you have to pass its legend as the first argument to `\ae\form\fieldset()` function. The returned object is a simple container with an array-like accessor/mutator:

```php
// ...
$form['about'] = \ae\form\fieldset('About you');
$form['about']['name'] = \ae\form\text('Name');
$form['about']['dob'] = \ae\form\date('Date of birth');
// ...
```

You can iterate its fields via any loop construct and access its legend via `legend` property to, say, render it:

```php
<fieldset>
    <legend><?= $fieldset->legend ?></legend>
<?php foreach($fieldset as $field): ?>
    <?= $field ?>
<?php endforeach; ?>
</fieldset>
```

#### Compound field

A compound field is a set of basic fields that by and large acts as a basic text field: its value is a string; you can apply validation constraints to it; it exposes the same properties and methods:

```php
$day = \ae\form\integer('Day')->min(1)->max(31);
$month = \ae\form\integer('Month')->min(1)->max(12);
$year = \ae\form\integer('Year')->min(1900)->max(2100);

$field = \ae\form\compound('Date', '2015-01-01')
    ->components($day, '/', $month, '/', $year)
    ->serialize(function ($array) {
        return str_pad($array[2], 4, '0') . '-' . 
            str_pad($array[1], 2, '0') . '-' . 
            str_pad($array[0], 2, '0');
    })
    ->unserialize(function ($string) {
        preg_match('(\d{4})-(\d{2})-(\d{2})', $string, $components);
        
        return [
            $components[3],
            $components[2],
            $components[1]
        ];
    })
    ->required('Please enter a valid date!')
    ->pattern(\ae\form\date);
```

In addition to regular methods, compound fields also exposes these:

- `components(...)` sets all components comprising the field, plus any filler strings.
- `serialize($function)` accepts a function that takes an array of individual component values and concatenates them.
- `unserialize($function)` accepts a function that breaks a string into an array of component values.

#### Repeater

A repeater is comprised of the same field set repeated several times:

```php
$name = \ae\form\text('Name')->required();
$email = \ae\form\email('Email address')->required();

$field = \ae\form\repeater('Invitation')
    ->repeat([
        'name' => $name,
        'email' => $email
    ], 'Invite more', 'Remove invitation')
    ->min_length(1)
    ->max_length(10);
```

It exposes three methods:

- `repeat($fields[, $add_label, $remove_label])` expects an associative array of repeated fields and (optionally) labels for add/remove buttons.
- `min_length($length)` applies a minimum length constraint; it is one by default.
- `max_length($length)` applies a maximum length constraint; there is no default maximum.

You can iterate and render the items manually:

```php
<?php foreach($repeater as $item): ?>
    <?= $item['name'] ?>
    <?= $item['email'] ?>
    <?= $item->remove_button(); ?>
<?php endforeach; ?>

<?= $repeater->add_button(); ?>
```

#### Sequence

A sequence field is comprised of several repeating field sets:

```php
$textarea = \ae\form\textarea('Content')
    ->attributes(['cols' => 50, 'rows' => 10]);
$image = \ae\form\file('Image')
    ->required()
    ->accept('image/*')
    ->min_dimensions(400, 400);
$align = \ae\form\radio('Align', 'left')
    ->required()
    ->options([
        'left' => 'Left',
        'center' => 'Center',
        'right' => 'Right',
    ]);

$field = \ae\form\sequence('Content')
    ->first('intro', [
        'text' => $textarea
    ], 'Add intro', 'Remove intro')
    ->always('background', [
        'image' => $image
    ])
    ->any('text', [
        'text' => $textarea
    ], 'Add text block')
    ->any('image', [
        'image' => $image,
        'align' => $align,
    ], 'Add image');
```




## Database

Database library lets you make queries to a MySQL database, and exposes simple object-oriented abstractions for individual tables and records.

Before we can do anything, you must configure the connection:

```php
\ae\db\configure([
    'host'     => 'localhost'
    'user'     => 'root'
    'password' => 'root'
    'database' => 'ae_db'
]);
```

Provided the connection parameters are correct and the database (<samp>ae_db</samp> in this example) exists, we can try to make a query:

```php
try {
    \ae\db\query("SELECT 1");
} catch (\ae\db\Exception $e) {
    echo 'Something went wrong: ' . $e->getMessage();
}
```

As you can see, whenever something goes wrong in the database layer, `\ae\db\Exception` is thrown. 

If you want to know what queries were made and how much memory and time they took, you should turn query logging on:

```php
\ae\inspector\show('queries', true);
```

You must show inspector first though, before you start making any queries! See [Inspector](#inspector) section for more information.


### Making queries 

Let's create a table named <samp>authors</samp> first:

```php
\ae\db\query("CREATE TABLE IF NOT EXISTS `authors` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `nationality` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
```

Now we can add data to this table:

```php
\ae\db\query("INSERT INTO `authors` ({data:names}) VALUES ({data:values})", null, [
        'name' => 'Richar K. Morgan', // (sic)
        'nationality' => 'Welsh', // (sic)
    ]);
    
// Which is equivalent to:
// \ae\db\query("INSERT INTO `authors` (`name`, `nationality`) VALUES ('Richar K. Morgan', 'Welsh')");

$morgan_id = \ae\db\insert_id();
```

In this example we used `{data:names}` and `{data:values}` placeholders and specified column names and corresponding values via the third argument.

Now, there's a typo in the author's name, so let's fix it:

```php
\ae\db\query("UPDATE `authors` 
    SET {data:set}, `name` = REPLACE(`name`, {from}, {to}) 
    WHERE `id` = {author_id}", [
        'from'      => 'Richar ',
        'to'        => 'Richard ',
        'author_id' => $morgan_id
    ], [
        'nationality' => 'British'
    ]);
    
// Which is equivalent to:

// \ae\db\query("UPDATE `authors` 
//    SET `nationality` = 'British', `name` = REPLACE(`name`, 'Richar ', 'Richard ') 
//    WHERE `id` = $morgan_id");
```

Here we used `{data:set}` placeholder and specified its value via the third argument. We also used three parameters in the query and specified their values via the second argument.

> **N.B.** You should always supply parameter and data values via the second and third arguments. The library escapes those values which prevents potential SQL injection attacks!

Now let's retrieve that record from the database:

```php
$result = \ae\db\query("SELECT * FROM `authors` WHERE `author_id` = {id}", [
        'id' => $morgan_id
    ]);

echo $result[0]->name . ' is ' . $result[0]->nationality; // Richard K. Morgan is British
```

### Query functions 

You can make any query via `\ae\db\query()` function alone, but it's not as convenient as specialized query functions:

- `\ae\db\select($table[, $columns])` – a <samp>SELECT</samp> query; accepts an array of column names as the second argument; returns a query object that can be used to add more clauses.
- `\ae\db\insert($table, $data[, ...])` – an <samp>INSERT</samp> query; returns the record as an object; if more than one record is inserted, returns an array of objects.
- `\ae\db\update($table, $predicate, $data)` – an <samp>UPDATE</samp> query; returns the number of rows affected.
- `\ae\db\delete($table, $predicate)` – a <samp>DELETE</samp> query; returns the number of rows affected.
- `\ae\db\find($table, $record_id)` – a custom <samp>SELECT</samp> query; returns a single record object or <samp>NULL</samp>, if no record is found.
- `\ae\db\count($table[, $column], $predicate)` – a custom <samp>SELECT</samp> query; returns a number of rows; if `$column` is specified, only distinct values are counted.
- `\ae\db\sum($table, $column, $predicate)` – a custom <samp>SELECT</samp> query; returns a sum of all column values.
- `\ae\db\average($table, $column, $predicate)` – a custom <samp>SELECT</samp> query; returns an average of all column values.

> All functions that use `$predicate`, do require it. If you want to, say, apply a <samp>DELETE</samp> query to all rows, you must use `\ae\db\all` constant. 
> 
> A predicate can be either an associative array of column name/value pairs, or an object returned by `\ae\db\predicate()` function, e.g. `\ae\db\predicate('a_column LIKE "%{value}%"', ['value' => 'foo'])`.

Let's insert more data:

```php
// Insert two more records
list($stephenson, $gibson) = \ae\db\insert('authors', [
        'name'        => 'Neal Stephenson',
        'nationality' => 'American'
    ], [
        'name'        => 'William Ford Gibson',
        'nationality' => 'Canadian'
    ]);
```

We should also update Mr. Morgan's nationality:

```php
\ae\db\update('authors', [
        'id' => $morgan_id
    ], [
        'nationality' => 'English'
    ]);
```

Now that we have more rows in the table, let's retrieve and display them in alphabetical order:

```php
$authors = \ae\db\select('authors')
    ->order_by('name', \ae\db\ascending);
$count = count($authors);

echo "There are $count authors in the result set:\n";

foreach ($authors as $author)
{
    echo "- {$author->name} ({$author->nationality})\n";
}
```

The example above will produce a list of authors in alphabetical order:

```txt
There are 3 authors in the result set:
- Neal Stephenson (American)
- Richard K. Morgan (English)
- William Ford Gibson (Canadian)
```

You can add clauses to the `\ae\db\select()` queries via chainable modifier methods: 

- `join($table, $on)`, `inner_join($table, $on)`, `left_join($table, $on)`, `right_join($table, $on)`, `full_join($table, $on)` – adds a <samp>JOIN</samp> clause; accepts table name as the first argument, and an associative array of foreign key/primary key pairs as the second argument; `join()` and `inner_join()` are synonyms.
- `where($predicate)` or `where($template, $parameters)` – adds a <samp>WHERE</samp> clause using a predicate object or by creating a predicate from template and parameters; multiple clauses are concatenated using <samp>AND</samp> operator.
- `group_by($column)` or `group_by($columns)` – adds a <samp>GROUP BY</samp> clause; accepts a column name or an array of column names.
- `having($predicate)` or `having($template, $parameters)` – adds a <samp>HAVING</samp> clause.
- `order_by($column[, $order])` or `order_by($columns_order)` – adds an <samp>ORDER BY</samp> clause; accepts a column name and an optional sort direction (`\ae\db\ascending` or `\ae\db\descending`), or an associative array of column/sort direction pairs.
- `limit($limit[, $offset])` – add a <samp>LIMIT</samp> clause.


### Active record

You might have noticed that `\ae\db\select()` and `\ae\db\insert()` functions return results as objects. In addition to exposing column values via properties, these objects also have three methods: `load()`, `save()`, and `delete()`.

In some cases you may want to update a property of an existing record without loading its data:

```php
$gibson = \ae\db\find('authors', $gibson_id);

$gibson->nationality = 'American';

$gibson->save();
```

Or you may want to load only a specific value from the database:

```php
$stephenson = \ae\db\find('authors', $stephenson_id);

$stephenson->load(['name']);

echo $stephenson->name; // echo "Neal Stephenson";
```

You can make a new record and save it to the database manually:

```php
$shaky = new \ae\db\Record('authors', [
        'name' => 'William Shakespeare',
        'nationality' => 'English'
    ]);

$shaky->save();
```

Now, Shakespeare was a playwright, and the rest of the authors are novelists, so let's delete his record:

```php
$shaky->delete();
```

You can extend `\ae\db\ActiveRecord` class to add more functionality to these objects:

```php
class Author extends \ae\db\ActiveRecord
{
    public static function table()
    {
        return 'authors';
    }
}
```

The base class implements several static methods that work exactly as corresponding `\ae\db\` functions, just without the first (`$table`) argument:

```php
Author::select([$columns]);
Author::insert($data[, ...]);
Author::update($predicate, $data);
Author::delete($predicate);
Author::find($record_id);
Author::count([$column,] $predicate);
Author::sum($column, $predicate);
Author::average($column, $predicate);
```

Of course, you can create a new record by instantiating the class:

```php
$leckie = new Author([
        'name' => 'Ann Leckie',
        'nationality' => 'American'
    ]);

$leckie->save();
```

Let's make things more interesting by introducing a second class of objects: <samp>books</samp>. First, we need to create a table to store them:

```php
\ae\db\query("CREATE TABLE IF NOT EXISTS `books` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `author_id` int(10) unsigned NOT NULL,
        `title` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
```

We also need a class to represent this table. The class name is totally arbitrary, so we will call it `Novel`:

```php
class Novel extends \ae\db\ActiveRecord
{
    public static function table()
    {
        return 'books';
    }
}
```

> There are two other methods you can override: `\ae\db\Table::accessor()` to return an array of primary keys; `\ae\db\Table::columns()` to return an array of data columns.

Now we could be adding new books using `Novel::insert()` method directly, but instead we will incapsulate this functionality into `add_novel()` method. We would also add `novels()` method for retrieving all novels written by an author:

```php
class Author extends \ae\db\ActiveRecord
{
    public static function table()
    {
        return 'authors';
    }
    
    public function add_novel($title)
    {
        return Novel::insert([
            'author_id' => $this->id,
            'title' => $title
        ]);
    }
    
    public function novels()
    {
        return Novel::select()
            ->where(['author_id' => $this->id]);
    }
}
```

Now let's add a few books to the database:

```php
$gibson->add_novel('Neuromancer');
$gibson->add_novel('Count Zero');
$gibson->add_novel('Mona Lisa Overdrive');

$stephenson->add_novel('Snow Crash');
$stephenson->add_novel('Cryptonomicon');
$stephenson->add_novel('Reamde');

// Note: we don't have to load author's record to add a novel!
$morgan = Author::find($morgan_id);

$morgan->add_novel('Altered Carbon');
$morgan->add_novel('Broken Angels');
$morgan->add_novel('Woken Furies');
```

And finally, let's add a method to `Novel` class that will return all book records sorted alphabetically:

```php
class Novel extends \ae\db\ActiveRecord
{
    public static function table()
    {
        return 'books';
    }
    
    public static function find_all()
    {
        return self::select()
            ->with('Author')
            ->order_by('title');
    }
}
```

The only new method in this example is `with()`. It adds a <samp>LEFT JOIN</samp> clause for <samp>authors</samp> table using <samp>author_id</samp> foreign key.

> We could manually specify the foreign key name via the second argument, and the property name via the third, e.g. `->with('Author', 'author_id', 'author')`, but they are automatically derived from class name and schema.

<!-- TODO: Polymorphic relationships. -->

Let's inventory our novel collection:

```php
$novels = Novel::find_all();
$count = count($novels);

echo "Here are all $count novels ordered alphabetically:\n";

foreach ($novels as $novel)
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


### Transactions

You should always make a sequence of interdependent database queries using a transaction to prevent a race condition and ensure data integrity:

```php
// Open transaction
$transaction = \ae\db\transaction();

// ...perform a series of queries...

$transaction->commit();

// ...perform another series of queries...

$transaction->commit();

// Close transaction (rolling back any uncommitted queries)
unset($transaction);
```

It could be that one of your queries fails, which will throw an exception and all uncommitted queries will be rolled back, when the `$transaction` object is destroyed.

> **N.B.** Only one transaction can exist at a time.


## Inspector

æ inspector is a builtin development tool you can use to debug and profile your application. Once you turn it on, all errors and notices generated by your app are captured and presented in a separate browser window:

![](./utilities/inspector/screenshot_log.png)

First of all, you must map all requests starting with <samp>/inspector</samp> to a handler returned by `\ae\inspector\assets()` function:

```php
\ae\request\map([
    // ...
    '/inspector' => \ae\inspector\assets(),
    // ...
]);
```

### Debugging

You can show the inspector (and start catching error messages) by calling `\ae\inspector\show()` function (the argument is optional; the following values are used by default):

```php
\ae\inspector\show([
    'globals'    => true,  // show global variables: $_GET, $_POST, $_SESSION, etc.
    'locals'     => false, // dump local variables on errors and warnings
    'queries'    => false, // show all database queries
    'backtraces' => true,  // show backtraces on errors and warnings
    'arguments'  => false, // dump function arguments when showing backtraces
]);
```

<!--
    TODO: Huge dumps? Should user care or should we prevent dumping huge things automatically.
-->


To log a message and/or dump a variable use `\ae\inspector\log()` function:

```php
$var = ['foo'];

\ae\inspector\log('This message will be logged with a dump of an array.', $var);
```

You can also log an error, warning, or notice:

```php
// Same thing, different color:
\ae\inspector\error('This is an error.');
\ae\inspector\warning('This is a warning.');
\ae\inspector\notice('This is a notice.');
```

If you don't want strings to be treated as messages, use `\ae\inspector\dump()` function instead:

```php
\ae\inspector\dump('This dump of a string is followed by dumps of a boolean and an array.', true, ['foo']);
```

### Profiling

You can profile your application's performance and memory footprint using `\ae\inspector\probe()`:

```php
// Create a probe
$probe = \ae\inspector\probe('Test probe')
	->mark('begin probing');

usleep(10000);

$probe->mark('slept for ~10ms');

$a = []; $j = 0; while($j < 10000) $a[] = ++$j;

$probe->mark('filled memory with some garbage');

unset($a);

$probe->mark('cleaned the garbage');
```

![](./utilities/inspector/screenshot_probe.png)


## Utilities

### File system paths

Several builtin function (`\ae\file()`, `\ae\image()`, `\ae\template()`, `\ae\layout()`) accept relative file paths as their argument. Internally, they all rely on path library to locate the actual file.

By default, all paths are resolved relative to the location of your main script. But you are encouraged to explicitly specify the root directory:

```php
\ae\path\configure('root', '/some/absolute/path');

$absolute_path = \ae\path('relative/path/to/file.ext');
```

A part of your application may need to resolve a path relative to its own directory. In this case, instead of changing the configuration back and forth (which is very error prone), you should save the path to that directory to a variable:

```php
$dir = \ae\path('some/dir');

$file = $dir->path('filename.ext'); // same as \ae\path('some/dir/filename.ext');
```

`\ae\path()` function and `path()` method always returns an object. You must explicitly cast it to string, when you need one:

```php
$path_string = (string) $path_object;
```

When you cast (implicitly or explicitly) a path object to a string, the library will throw an `\ae\path\Exception`, if the path does not exist. If such behavior is undesirable, you should use `exists()`, `is_directory()`, and `is_file()` methods first to check, whether the path exists, and points to a directory or file.

You can iterate path segments using `foreach`, `for`, and `while` loops:

```php
$path = \ae\path('path/that/may/not/exist');
$absolute_path = '';

foreach ($path as $segment)
{
    $absolute_path.= '/' . $segment;
}

echo $absolute_path;
```

### Exception safety

æ is designed with strong exception safety in mind. You can make you code exception-safe too by taking advantage of the object life cycle. 

> `__construct()` method is called whenever a new object is instantiated. If the object is assigned to a variable, it will persist until either:
> 
> - the variable is `unset()`
> - the scope where the variable was declared is destroyed, either naturally or *when an exception is thrown*
> - execution of the program halts
> 
> `__destruct()` method is called when there are no more variables pointing to the object.

If you find yourself cleaning up state after catching an exception, you are doing it wrong! Generally speaking, all resources your object has allocated must be deallocated in the destructor. But writing little wrapper-classes to manage each kind of resource is simply too tedious!

If you just need to ensure some (usually global or static) variable is set to its previous state, use `\ae\switcher()` to create an object that will do it automatically:

```php
$a = 'foo';

$switcher = \ae\switcher($a, 'bar');

// $a === 'bar'

unset($switcher);

// $a === 'foo'
```

And if you need to run some arbitrary code (to free resources for instance), use `\ae\deferrer()`:

```php
$file = fopen('some/file', 'r');

$deferrer = \ae\deferrer(function () use ($file) {
    fclose($file);
});
```

### Configuration options

While most æ libraries come with sensible defaults, they also allow you to configure their behavior via `\ae\*\configure()` functions. Internally, all of them use `\ae\options()` function to create an object that:

1. enumerates all possible option names
2. provides default values for each option
3. exposes an array-like interface to get and set values
4. ensures that only declared option names are used
5. validates value types.

Let's declare a simple set of options:

```php
$options = \ae\options([
    'foo' => true,
    'bar' => [],
    'baz' => null,
]);
```

And that's how we can use it:

```php
if (true === $options['foo']) {
    $options['bar'] = [1, 2, 3];
}

$options['baz'] = 'How do you do?';
```

Please note that <samp>baz</samp> can be set to any value type, because its default value is <samp>null</samp>. On the other hand, the following code will throw an `\ae\options\Exception`:

```php
$options['foo'] = null; // Exception: foo can only be TRUE or FALSE
```

You can also set option values by passing an associative array via second argument to `\ae\options()`. Those values are subject to validation rules listed above. This is useful when you want to both declare and use the options object to configure, say, your library:

```php
class MyLibrary
{
    protected static $options;
    
    public static function configure(/* $values OR $name, $value*/)
    {
        $args = func_get_args();
        $values = func_num_args() > 1 ? [ $args[0] => $args[1] ] ? $args[0];
        
        static::$options = \ae\options([
            // options names and their default values
        ], $values);
    }
}
```

### Unit testing

Most examples in this documentation both illustrate how things work and serve as unit tests.

Each example listing is a separate, optionally accompanied by reference output, which can be a plain text document, an image, or any other file. You pass paths to both files to `\ae\example()` function, it lints and runs the script, optionally compares it to reference file, and (if no errors were encountered) outputs the source code:

```php
echo \ae\example('path/to/example.php', 'path/to/output.txt');
```

If you want to display the output of the script as well, just use `\ae\example()` again passing path to output file as the first argument:

```php
echo \ae\example('path/to/output.txt');
```

Not everything in your script is relevant to the end user, who just wants to know how to use your code. You can turn source output on and off using `// +++` and `// ---` comments:

```php
<?php
// ---

// This part will be cut out from the source code shown in the documentation,
// so you can secretly set everything up for the test.

$_GET['what'] = 'world';

// +++
// GET /index.php?what=world HTTP/1.1

echo 'Hello ' . $_GET['what'] . '!';

// ---

// You should reset all variables you changed to previous state
unset($_GET['what']);

// You can also check state and trigger an error or throw an exception 
// manually, if something is not right:

if (1 === 2) {
    throw new Exception('Cats are sleeping with dogs!');
}

// +++
?>
```

Which in the documentation will look like this:

```php
<?php
// GET /index.php?what=world HTTP/1.1

echo 'Hello ' . $_GET['what'] . '!';

?>
```



## License 

Copyright 2011-2016 Anton Muraviev <anton@goodmoaning.me>

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this project except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.