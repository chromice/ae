> Looking for previous version? Try [this branch](https://github.com/chromice/ae/tree/deadend-1.0).

# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for request routing, response caching, templating, form validation, image manipulation, database operations, and easy debugging and profiling.

This project has been created by its sole author to explore, express and validate his views on web development. As a result, this is an opinionated codebase that attempts to achieve the following goals:

- **Simplicity:** There are no controllers, event emitters and responders, filters, template engines. There are no config files to tinker with either: most libraries come preconfigured with sensible default values.
- **Reliability**: The APIs were designed to be expressive and user error-resistant. All functionality described in this document is covered with tests.
- **Performance:** All libraries have been designed with performance and efficiency in mind. Responses can be cached statically and served by Apache alone.
- **Independence:** This toolkit does not have any third-party dependencies and the codebase is intentially small and clean, so that anyone can understand how something works, or why it does not work.

There is nothing particularly groundbreaking or fancy about this toolkit. If you just need a lean PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com).

æ will be perfect for you, if your definition of a web application falls along these lines:

> A web application is a bunch of scripts thrown together to concatenate a string of text (HTTP response) in response to another string of text (HTTP request).

In other words, æ will not let you forget that most of the back-end code is a glorified string concatenation, but it will alleviate the most cumbersome aspects of it. 

In more practical terms, if you are putting together a site with some forms that save data to a database, and then present that data back to the user on a bunch of pages, æ comes with everything you need.

You may still find it useful, even if you are thinking of web app architecture in terms of dispatchers, controllers, events, filters, etc. The author assumes you are working on something complex and wishes you a hearty good luck!

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
- [Path](#path)
- [File](#file)
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
    - [Migrations](#migrations)
- [Inspector](#inspector)
    - [Debugging](#debugging)
    - [Profiling](#profiling)
- [Utilities](#utilities)
    - [Exception safety](#exception-safety)
    - [Configuration options](#configuration-options)
- [Testing](./TESTING.md)
- [License](./LICENSE.md)

* * *


## Getting started

### Platform requirements

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

Let's create the most basic of web applications. Create a file named <samp>index.php</samp> in the web root directory and paste this code into it:

```php
<?php require 'path/to/ae/core.php';

$path = \ae\request\path();

echo \ae\request\method() . ' ' . $path . ' HTTP/1.1';

echo 'Hello ' . ( isset($path[0]) ? $path[0] : 'world' ) . '!';

?>
```

Now let's instruct Apache to redirect all unresolved URIs to <samp>index.php</samp>, by adding the following rules to <samp>.htaccess</samp> file in the web root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Now, if you open our app at, say, <samp>http://localhost/</samp> in a browser, you should see this:

```txt
GET / HTTP/1.1

Hello world!
```

If you change the address to <samp>http://localhost/universe</samp>, you should see:

```txt
GET /universe HTTP/1.1

Hello universe!
```

Now let's familiarise you with all the libraries.


## Request

Request library is a lightweight abstraction of HTTP requests that let's you do the following:

- Distinguish between different request methods via `\ae\request\method()` function:

    ```php
    if (\ae\request\method() === \ae\request\GET) {
        echo "This is a GET request.";
    } else if (\ae\request\method() === \ae\request\POST) {
        echo "This is a POST request.";
    }
    ```

- Access URI path segments via `\ae\request\path()` function diectly or via path object it returns when called with no argument:

    ```php
    // GET /some/arbitrary/script.php HTTP/1.1

    $path = \ae\request\path();
    
    $path; // 'some/arbitrary/script.php'

    $path[0]; // 'some'
    $path[1]; // 'arbitrary'
    $path[2]; // 'script.php'

    $path[-3]; // 'some'
    $path[-2]; // 'arbitrary'
    $path[-1]; // 'script.php'
    
    \ae\request\path(2); // 'script.php'
    \ae\request\path(3); // NULL
    \ae\request\path(3, 'fallback'); // 'fallback'
    ```

- Get the expected response extension (<samp>html</samp> by default), which is determined by the *extension* part of the URI path.

    ```php
    // GET /some/arbitrary/request.json HTTP/1.1

    \ae\request\extension(); // 'json'
    ```

- Get the client IP address via `\ae\request\address()` function.
    
    > **N.B.** If your app is running behind a reverse-proxy and/or load balancer, you must specify their IP addresses first
    
    ```php
    \ae\request\configure('proxies', ['83.14.1.1', '83.14.1.2']);

    $client_ip = \ae\request\address(); 
    ```
    
- Access `$_GET` query arguments and `$_POST` data via `\ae\request\query()` and `\ae\request\data()` functions respectively:

    ```php
    // POST /search HTTP/1.1
    // 
    // term=foo
    
    $get = \ae\request\query(); // $_GET
    $post = \ae\request\data(); // $_POST

    \ae\request\query('action', 'search'); // 'search'
    \ae\request\data('term'); // 'foo'
    ```

- Access uploaded files (when request body is encoded as <samp>multipart/form-data</samp>) via `\ae\request\files()` function.

    ```php
    $files = \ae\request\files();
    // returns an associative array of uploaded files:
    // e.g. ['form_input_name' => \ae\file(), ...]
    ```
    
- Get a request header via `\ae\request\header()` function:

    ```php
    $charset = \ae\request\header('Accept-Charset'); // 'utf-8'
    ```

- Access raw request body, use `\ae\request\body()` function:

    ```php
    $raw = \ae\request\body(); // file_get_contents("php://input")
    ```

- Map requests to a function/method or instance of a class that implements `\ae\response\Dispatchable` interface.


### Request mapping

You should always strive to break down your application into smallest independent components. The best way to handle a request is to map it to a specific function or template that encapsulates a part of your application's functionality.

Requests are mapped using rules, key-value pairs of a path pattern and *either* an object that conforms to `\ae\response\Dispatchable` interface *or* a function/method that returns such an object.

Here's an example of a request (<samp>GET /about-us HTTP/1.1</samp>) being mapped to a page template (<samp>about-us-page.php</samp>):

```php
// GET /about-us HTTP/1.1

\ae\request\map([
    // ...
    '/about-us' => \ae\template('path/to/about-us-page.php'),
    '/our-work' => \ae\template('path/to/our-work-page.php'),
    // ...
]);
```

If the template file does not exist, `\ae\template()` will throw an `\ae\path\Exception`, which in turn will result in `\ae\response\error(404)` being dispatched.

> **N.B.** If a request handler throws `\ae\path\Exception`, `\ae\response\error(404)` is dispatched. If it throws any other exception, `\ae\response\error(500)` is dispatched instead.

Now, let's enable users to download files from a specific directory:

```php
// GET /download/directory/document.pdf HTTP/1.1

\ae\request\map([
    // ...
    '/download' => function ($file_path) {
        return \ae\file('path/to/downloadable/files/' . $file_path)
        // returns '/path/to/downloadable/files/directory/document.pdf' file, if it exists
            ->attachment();
    },
    // ...
]);
```

First of all, we take advantage of the fact that `\ae\file()` function returns an object that conforms to `\ae\response\Dispatchable` interface. Secondly, whenever actual matched URI path is longer than the pattern, the remainder of it is passed as *last argument* to our handler. And thirdly, we use `attachment()` method to set <samp>Content-Disposition</samp> header to <samp>attachment</samp>, and force the download rather than simply pass the file content through.

> You can pass a custom file name to `attachment()` method as the first argument, if you do not want to use the actual name of the file.

Image processing is a very common problem that can be solved in multiple ways. Let's create a simple image processor that accepts an image file path, resizes it to predefined dimensions, and caches the result for 10 years:

```php
// GET /resized/square/avatars/photo.jpg HTTP/1.1

\ae\request\map([
    // ...
    '/resized/{alpha}' => function ($format, $path) {
        switch ($format) {
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
            ->cache(10 * \ae\cache\year, \ae\cache\server);
    },
    // ...
]);
```

Similarly to the file download example, the file path is passed as *the last argument* to our handler. In addition to that, we catch the image format as *the first argument*. The object returned by `\ae\image()` conforms to `\ae\response\Cachable` interface (in addition to `\ae\response\Dispatchable`) and implements `cache()` method.

> There are also: `{numeric}` placeholder that matches and captures only numeric characters; `{any}` placeholder can match (and capture) a substring only within one path segment, i.e. it can match any character other than <samp>/</samp> (forward slash).

Now let's write a more generic rule that handles all root level pages by using a placeholder, and mapping it to a function:

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

Here we use `{any}` placeholder to catch the slug of the page and pass its value to our handler function as the first argument.

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

> **N.B.** All rules are processed in sequence. You should always put rules with higher specificity at the top. <samp>'/'</samp> is the least specific rule and will match *any* request.


## Response

Response library is a set of functions, classes, and interfaces that lets you create a response object, set its content and headers, and (optionally) cache and compress it. It is designed to work with `\ae\request\map()` function (see above), which expects you to create a response object for each request.

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
    ->cache(5 * \ae\cache\minute, \ae\cache\server)
    ->dispatch('hello-world.html');

?>
```

When response object is created, it starts buffering all output. Once the `dispatch()` method is called, the buffering stops, HTTP headers are set, and content is output.

> You must explicitly specify the response path when using `dispatch()` method. To create a response for the current request use `\ae\request\path()` function.

By default all responses are <samp>text/html</samp>, but you can change the type by either setting <samp>Content-type</samp> header to a valid mime-type or appending an appropriate file extension to the dispatched path, e.g. <samp>.html</samp>, <samp>.css</samp>, <samp>.js</samp>, <samp>.json</samp> 

> **N.B.** Objects returned by `\ae\response()`, `\ae\buffer()`, `\ae\template()`, `\ae\file()`, `\ae\image()` implement `\ae\response\Dispatchable` interface, which allows you to dispatch them. You should refrain from using `dispatch()` method explicitly though, and use the request mapping pattern described previously.


### Buffer

You can create a buffer and assign it to a variable to start capturing output. All output is captured until the instance is destroyed:

<!--
     or buffered content is used ???
-->

```php
$buffer = \ae\buffer();

echo "I'm buffered!";

$content = (string) $buffer; // "I'm buffered!"

echo "I'm still buffered!";

unset($buffer);

echo "And I'm not buffered!";
```

> `\ae\buffer()` returns an instance of `\ae\Buffer` class, which implements `__toString()` magic method that always returns a string currently contained in the buffer.


### Template

Use `\ae\template()` to capture output of a parameterized script:

```php
echo \ae\template('path/to/template.php', [
    'title' => 'Example!',
    'body' => '<h1>Hello world!</h1>'
]);
```

Provided the content of <samp>template.php</samp> is:

```php
<title><?= $title ?></title>
<body><?= $body ?></body>
```

...the script will output:

```html
<title>Example!</title>
<body><h1>Hello world!</h1></body>
```

> `\ae\template()` returns an instance of `\ae\Template` class, which implements `__toString()` magic method that renders the template with specified parameters.


### Layout

Layout library allows you to wrap output of a script with output of another script. The layout script is executed *last*, thus avoiding many problems of using separate header and footer scripts to keep the template code [DRY](http://en.wikipedia.org/wiki/DRY).

Here's an example of a simple layout <samp>layout_html.php</samp>:

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

Objects returned by `\ae\response()`, `\ae\buffer()`, `\ae\template()`, `\ae\file()`, `\ae\image()` implement `\ae\response\Cacheable` interface, which allows you to call their `cache()` method to cache them for a number of minutes:

- `->cache(60)` will simply set <samp>Cache-Control</samp>, <samp>Last-Modified</samp>, and <samp>Expires</samp> headers
- `->cache(60, \ae\cache\server)` will save the response to the server-side cache as well

You can also use cache functions directly:

- Save any response manually using `\ae\cache\save()` function:

    ```php
    \ae\cache\save('hello-world.html', $response, 2 * \ae\cache\hour);
    ```

- Delete any cached response via `\ae\cache\delete()` function by passing full or partial URL to it:

    ```php
    \ae\cache\delete('hello-world.html');
    ```

- Remove all *stale* cache entries via `\ae\cache\clean()`:

    ```php
    \ae\cache\clean();
    ```

- Erase all cached data completely via `\ae\cache\purge()` function:

    ```php
    \ae\cache\purge();
    ```

> **N.B.** Cache cleaning and purging can be resource-intensive and should not be performed while processing a regular user request. You should create a dedicated cron script or use some other job queueing mechanism for that.

#### Configuration

The responses are saved to <samp>cache</samp> directory (in the web root directory) by default. For caching to work correctly this directory must exist and be writable. You must also configure Apache to look for cached responses in that directory:

1. Put the following rules into <samp>.htaccess</samp> file in the web root directory:

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

With everything in place, Apache will first look for an unexpired cached response, and only if it finds nothing, will it route the request to <samp>index.php</samp> in the web root directory.

You can change cache directory location like this:

```php
\ae\cache\configure('directory', 'path/to/cache');
```

## Path

All functions thar accept relative file paths as an argument rely on path library to resolve them.

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

`\ae\path()` function and `path()` method always return an object. You must explicitly cast it to string, when you need one:

```php
$path_string = (string) $path_object;
```

When you cast (implicitly or explicitly) a path object to a string, the library will throw an `\ae\path\Exception`, if the path does not exist. If such behavior is undesirable, you should use `exists()`, `is_directory()`, and `is_file()` methods first to check, whether the path exists, and points to a directory or file.

You can iterate path segments and access them individually using an index:

```php
$path = \ae\path('some/file/path.txt');
$absolute_path = '';

foreach ($path as $segment) {
    $absolute_path.= '/' . $segment;
}

echo $absolute_path;

$path[-3]; // 'some'
$path[-2]; // 'file'
$path[-1]; // 'path.txt'
```


## File

File library uses standard file functions: `fopen()`, `fclose()`, `fread()`, `fwrite()`, `copy()`, `rename()`, `is_uploaded_file()`, `move_uploaded_file()`, etc. All methods throw `\ae\file\Exception` on error.

- Open and lock the file, and read and write its content:

    ```php
    $file = \ae\file('path/to/file.txt', \ae\file\writable & \ae\file\locked)
        ->truncate()
        ->append('Hello', ' ')
        ->append('World');
    
    $file->content(); // 'Hello World'

    // Unlock file and close its handle
    unset($file);
    ```

- Access basic information about the file:

    ```php
    $file = \ae\file('path/to/file.txt');

    $file->size(); // 12
    $file->mime(); // 'text/plain'
    $file->name(); // 'file.txt'
    $file->extension(); // 'txt'

    $file->path(); // \ae\path('path/to/file.txt')
    ```

- Copy, move, and delete existing files:

    ```php
    $file = \ae\file('path/to/file.txt');

    if ($file->exists()) {
        $copy = $file->copy('./file-copy.txt');
        $file->delete();
        $copy->move('./file.txt');
    }
    ```

- Handle uploaded files:

    ```php
    $file = \ae\file($_FILES['file']['tmp_name']);

    if ($file->is_uploaded()) {
        $file->moveTo('path/to/uploads/')->rename($_FILES['file']['name']);
    }
    ```
    
    <!-- FIXME: What if file name is a dangerous relative path? -->

- Assign arbitrary metadata to a file, e.g. database keys, related files, alternative names, etc.:

    ```php
    $file['real_name'] = 'My text file (1).txt';
    $file['resource_id'] = 123;

    foreach ($file as $meta_name => $meta_value) {
        echo "{$meta_name}: $meta_value\n";
    }
    ```
    
    <!--
    ```txt
    real_name: My text file (1).txt
    resource_id: 123
    ```
    -->

    > **N.B.** Metadata is transient and is never saved to disk, but it may be used by different parts of your application to communicate additional information about the file.

- Keep file size calculations readable:

    ```php
    \ae\file\byte;     // 1
    \ae\file\kilobyte; // 1000
    \ae\file\kibibyte; // 1024
    \ae\file\megabyte; // 1000000
    \ae\file\mebibyte; // 1048576
    \ae\file\gigabyte; // 1000000000
    \ae\file\gibibyte; // 1073741824
    \ae\file\terabyte; // 1000000000000
    \ae\file\tebibyte; // 1099511627776
    \ae\file\petabyte; // 1000000000000000
    \ae\file\pebibyte; // 1125899906842624
    ```


## Image

Image library is a wrapper around standard GD library functions. It lets you effortlessly resize, crop and apply filters to an image, and also:

- Retrieve basic information about the image:

    ```php
    $image = \ae\image('example/image_320x240.jpg');
    
    // image-specific info
    $image->width();  // 320
    $image->height(); // 240
    
    // general file info
    $image->size(); // 1024
    $image->mime(); // 'image/jpeg'
    $image->name(); // 'image_320x240.jpg'
    $image->extension(); // 'jpg'
    ```

- Copy, move, and delete the image file:

    ```php
    $image = \ae\image('path/to/image.jpg');

    if ($image->exists()) {
        $copy = $image->copy('./image-copy.jpg');
        $image->delete();
        $copy->move('./image.jpg');
    }
    ```


### Resizing and cropping

You can transform the image by changing its dimensions in 4 different ways:

- `scale($width, $height)` scales the image in one or both dimensions; pass `null` for either dimension to scale proportionally.
- `crop($width, $height)` crops the image to specified dimensions; if the image is smaller in either dimension, the difference will be padded with transparent pixels.
- `fit($width, $height)` scales the image, so it fits into a rectangle defined by target dimensions.
- `fill($width, $height)` scales and crops the image, so it completely covers a rectangle defined by target dimensions.

You will rarely need the first two methods, as the latter two cover most of use cases:

```php
$photo = \ae\image('path/to/photo.jpg');

$regular = $photo
    ->fit(1600, 1600)
    ->suffix('_big')
    ->save();

$thumbnail = $photo
    ->fill(320, 320)
    ->suffix('_thumbnail')
    ->save();
```

You can specify the point of origin using `align()` method, before you apply `crop()` and `fill()` transformations to an image:

```php
$photo
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
- `colorize($red, $green, $blue)` changes the average color of the image; accepts numbers from <samp>0.0</samp> to <samp>1.0</samp>.
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
    ->save();
// image is saved as 'some/unknown_photographic_image.jpg'
```

If you want to change the name *or* image type completely, you should provide file name to `save()` method:

```php
$image = \ae\image('some/image.png');

$image
    ->quality(75)
    ->progressive(true)
    ->save('image.jpg');
// image is saved as 'some/image.jpg'

$image
    ->interlaced(true)
    ->save('image.gif');
// image is saved as 'some/image.gif'
```


## Form

Form library lets you create web forms and validate them both on the client and the server sides using HTML5 constraints. Both forms and individual controls render themselves into valid HTML when cast to string (by implementing `__toString()` magic method), but you can render them manually, if you so desire.


### Declaration

You can create a new form using `\ae\form()` function. It takes form name as the first argument, and (optionally) an array of initial values as the second argument:


```php
$form = \ae\form('Profile', [
        'name' => 'John Connor',
    ]);
```

> **N.B.** The form name must be unique within the context of a single web page.

Once form is created, you can assign individual form fields to it:

```php
$form['name'] = \ae\form\text('Full name')
    ->required()
    ->max_length(100);

$form['email'] = \ae\form\email('Email address')
    ->required();

$form['phone'] = \ae\form\tel('Phone number'); 
// N.B. Never use \ae\form\number() for phone numbers!

$form['phone_type'] = \ae\form\radio('Is it home, work, or mobile number?', 'home')
    ->options([
        'home' => 'Home number',
        'work' => 'Work number',
        'mobile' => 'Mobile number',
    ])
    ->required();

$form['birth_date'] = \ae\form\date('Date of birth')
    // compare to date 18 years ago using strtotime()
    ->max('-18 years', 'You must be at least 18 years old!');

$form['photos'] = \ae\form\image('Photos of you')
    ->multiple()
    ->min_width(200)
    ->min_height(200)
    ->max_size(10 * \ae\file\megabyte);
```

> You can assign an instance of any subclass of `\ae\form\DataField` or `\ae\form\FileField` classes to a form. Once field is assigned, the form will use either `$_POST` or `$_FILES` array as its data source, depending on which parent class the object is related to.

Field objects expose methods that you can use to define their validation constraints. Most of those constraints behave similar to their HTML5 counterparts. See [Constraints](#validation-constraints) section for more information.

#### Basic field types

Form library supports most kinds of `<input>`, `<select>`, or `<textarea>` fields out of the box. All field factory functions accept a field label as the first argument, and (optionally) an initial value(s) as the second argument:

> You can specify initial values when creating both form and field objects. However, initial values of the form always override initial values of its fields.

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
- `\ae\form\file()` and `\ae\form\image()` both use <samp>&lt;input type="file"&gt;</samp> control; will accept multiple values, if `multiple()` method is called.


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
- `\ae\form\image()`
- `\ae\form\select()`

> `\ae\form\checkbox()` does not have `multiple()` method, but the field will accept more than one value, if you provide multiple options via `options()` method.

#### Validation constraints

Most validation constraints are field type-specific, but all field types have access to the following constraints:

- `required()` – the field must contain a non-empty a value; corresponds to <samp>required</samp> attribute in HTML5.
- `valid($function)` allows you to specify an arbitrary constraint using a *callable* `$function`; current field value is passed as the only argument; function must return either `true`, if the value is valid, or an error message:

```php
$field = \ae\form\text('First name')
    ->valid(function ($value) {
        return $value === 'Anton' ? 'Sorry Anton, cannot let you through!' : true;
    });
```

> **N.B.** All validation constraints presented below will generate human-readable error messages automatically. If you wish to override the default error message, you can do so by passing your error message (or an anonymous function that returns one) as *the last argument*.

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

##### Image field constraints

- `min_width($width)`, `max_width($width)`, `min_height($height)`, `max_height($height)`, `min_dimensions($width, $height)`, `max_dimensions($width, $height)` define image dimension constraints.


### Validation

Once the form is declared, you can check if it has been submitted, and if the submitted values are valid:

```php
if ($form->is_submitted() && $form->is_valid()) {
    // All HTML5 constraints are met
    // Time for custom validation
    $is_valid = true;
    
    // Validate name
    if ($form['name']->value === 'John Connor') {
        $form['name']->error = 'You are not John Connor! State your real name please.';
        $is_valid = false;
    }
    
    // If name is valid, save the data
    if ($is_valid) {
        $user = Profile::find($user_id);
        
        foreach ($form as $name => $field) {
            $user->$name = $field->value;
        }
        
        $user->save();
        
        echo '<p class="message success">Successfully saved!</message>';
    }
}
```

When `is_valid()` method is called, the form will iterate all its fields, calling their `validate()` method. All validation constraints that were set prior to that are checked at this stage.


### Presentation

In order to render a form into HTML you can simply cast it to a string:

```php
echo (string) $form;
```

Alternatively, you can render the form by manually calling `open()` and `close()` methods to create `<form>` and `</form>` (and a few hidden) tags, and iterating all its fields and rendering them individually:

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
- `name` contains the field name, e.g. <samp>name</samp>, <samp>options[]</samp>, <samp>repeater[0][name]</samp>, etc. set by the form object when the field is assigned to it.
- `error` contains an error message set during validation, e.g. <samp>Name is required</samp>.
- `value` contains current value(s), either submitted or default.
- `classes` contains a string of HTML classes indicating the state of the field, e.g. <samp>text-field required-field</samp>.

The following method render individual components of a field:

- `label([$attributes])` renders field label, e.g. <samp>&lt;label for=&quot;field-id&quot;&gt;Field label&lt;/label&gt;</samp>; returns an empty string for `\ae\form\checkbox()` fields with no options.
- `control([$attributes])` renders field control, e.g. <samp>&lt;input type=&quot;text&quot; name=&quot;name&quot; value=&quot;&quot;&gt;</samp>.
- `error([$before = '<em class="error">', $after = '</em>'])` renders field error, if it has one.

### Localization 

All validation constraints generate human-readable error messages automatically:

```php
$form['name'] = \ae\form\text('Full name')->required()->max_length(100);
```

You can change the default validation error message by passing a string as the last argument to any validation constrain function: 

```php
$form['name'] = \ae\form\text('Full name')
    ->required('Please state your full name.')
    ->max_length(100, 'Your name is too long!');
```

You can also customize error messages based on value by passing an anonymous function instead of a string: 

```php
$form['name'] = \ae\form\text('Full name')
    ->required('Please state your full name.')
    ->max_length(100, function ($value) {
        $diff = strlen($value) - 100;
        return 'Your name is ' . $diff . ' character' . ($diff !== 1 ? 's' : '') . ' longer than what is acceptable!'
    });
```

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

A compound field is a set of basic fields that by and large acts as a text field: its value is a string; you can apply validation constraints to it; it exposes the same properties and methods:

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

- `components(...)` sets all components comprising the field, including any filler strings.
- `serialize($function)` accepts a function that takes an array of individual component values and concatenates them.
- `unserialize($function)` accepts a function that breaks a string into an array of component values.

#### Repeater

A repeater is comprised of the same field set repeated several times:

```php
$name = \ae\form\text('Name')->required();
$email = \ae\form\email('Email address')->required();

$repeater = \ae\form\repeater('Invitation')
    ->repeat([
        'name' => $name,
        'email' => $email
    ], 'Invite more', 'Remove invitation')
    ->min_length(1)
    ->max_length(10);
```

It exposes three methods:

- `repeat($fields[, $add_label, $remove_label])` expects an associative array of repeated fields and (optionally) labels for add/remove buttons.
- `min_length($length)` applies a minimum length constraint; <samp>1</samp> by default.
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

A sequence field is comprised of several repeating blocks of fields:

```php
$textarea = \ae\form\textarea('Content')
    ->attributes(['cols' => 50, 'rows' => 10]);
$image = \ae\form\image('Image')
    ->required()
    ->min_dimensions(400, 400);
$align = \ae\form\radio('Align', 'left')
    ->required()
    ->options([
        'left' => 'Left',
        'center' => 'Center',
        'right' => 'Right',
    ]);

$sequence = \ae\form\sequence('Content')
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

It exposes several methods that expect the block name, an associative array of its fields, and (optionally) labels for add/remove buttons:

- `any($name, $fields[, $add_label, $remove_label])` defines a block that can appear at any point in the sequence.
- `first($name, $fields[, $add_label, $remove_label])` defines a block that will always appear first and can only be added once.
- `last($name, $fields[, $add_label, $remove_label])` defines a block that will always appear last and can only be added once.
- `always($name, $fields)` defines a block that is always present once and cannot be removed.


You can iterate and render the blocks manually:

```php
<?php foreach($sequence as $block): ?>
    <?php if ($block->type === 'background'): ?>
        <?= $block['image'] ?>
    <?php if ($block->type === 'image'): ?>
        <?= $block['image'] ?>
        <?= $block['align'] ?>
    <?php else: ?>
        <?= $block['text'] ?>
    <?php endif ?>
    <?= $block->remove_button(); ?>
<?php endforeach; ?>

<?= $sequence->add_buttons(); ?>
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

If something goes wrong in the database layer, `\ae\db\Exception` is thrown. 

If you want to know what queries were made and how much memory and time they took, you should turn query logging on:

```php
\ae\inspector\show('queries', true);
```

You must show inspector before you start making any queries! See [Inspector](#inspector) section for more information.


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

> **N.B.** You should always supply parameter and data values via the second and third arguments. The library escapes those values if necessary by wrapping them in quotes and treating special characters to prevent SQL injection attacks.
> 
> If you require a raw parameter value (e.g. you want to pass a statement), use `{raw:value}` placeholder. If you want an escaped value, but want to wrap it in quotes yourself (e.g. you are making a `LIKE` comparison against a variable), use `{escaped:value}` placeholder.

Now let's retrieve that record from the database:

```php
$result = \ae\db\query("SELECT * FROM `authors` WHERE `author_id` = {id}", [
        'id' => $morgan_id
    ]);

$result[0]->name . ' is ' . $result[0]->nationality; // 'Richard K. Morgan is British'
```

### Query functions 

You can make any query just with `\ae\db\query()` function alone, but it's not as convenient as using one of specialized query functions:

- `\ae\db\select($table[, $columns])` – a <samp>SELECT</samp> query; accepts an array of column names as the second argument; returns a query object that can be used to add more clauses.
- `\ae\db\insert($table, $data)` – an <samp>INSERT</samp> query; returns the record as an object.
- `\ae\db\replace($table, $data)` – a <samp>REPLACE</samp> query; returns the record as an object.
- `\ae\db\update($table, $data, $predicate)` – an <samp>UPDATE</samp> query; returns the number of rows affected.
- `\ae\db\delete($table, $predicate)` – a <samp>DELETE</samp> query; returns the number of rows affected.
- `\ae\db\find($table, $record_id)` – a custom <samp>SELECT</samp> query; returns a single record object or <samp>NULL</samp>, if no record is found.
- `\ae\db\count($table[, $column], $predicate)` – a custom <samp>SELECT</samp> query; returns a number of rows; if `$column` is specified, only distinct values are counted.
- `\ae\db\sum($table, $column, $predicate)` – a custom <samp>SELECT</samp> query; returns a sum of all column values.
- `\ae\db\average($table, $column, $predicate)` – a custom <samp>SELECT</samp> query; returns an average of all column values.

> All functions that use `$predicate` do require it. If you want to, say, apply a <samp>DELETE</samp> query to all rows, you must use `\ae\db\all` constant. 
> 
> A predicate can be either an associative array of column name/value pairs, or an object returned by `\ae\db\predicate()` function, e.g. `\ae\db\predicate('a_column LIKE "%{escaped:value}%"', ['value' => 'foo'])`.

Let's insert more data:

```php
// Insert two more records
$stephenson = \ae\db\insert('authors', [
        'name'        => 'Neal Stephenson',
        'nationality' => 'American'
    ]);
$gibson = \ae\db\insert('authors', [
        'name'        => 'William Ford Gibson',
        'nationality' => 'Canadian'
    ]);
```

We should also update Mr. Morgan's nationality:

```php
\ae\db\update('authors', [
        'nationality' => 'English'
    ], [
        'id' => $morgan_id
    ]);
```

Now that we have more rows in the table, let's retrieve and display them in alphabetical order:

```php
$authors = \ae\db\select('authors')
    ->order_by('name', \ae\db\ascending);
$count = count($authors);

echo "There are $count authors in the result set:\n";

foreach ($authors as $author) {
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
- `where($predicate)` or `where($template, $parameters)` – adds a <samp>WHERE</samp> clause using a predicate object or by creating a predicate from template string and parameters; multiple clauses are concatenated using <samp>AND</samp> operator.
- `group_by($column)` or `group_by($columns)` – adds a <samp>GROUP BY</samp> clause; accepts a column name or an array of column names.
- `having($predicate)` or `having($template, $parameters)` – adds a <samp>HAVING</samp> clause.
- `order_by($column[, $order])` or `order_by($columns_order)` – adds an <samp>ORDER BY</samp> clause; accepts a column name and an optional sort direction (`\ae\db\ascending` or `\ae\db\descending`), or an associative array of column/sort direction pairs.
- `limit($limit[, $offset])` – add a <samp>LIMIT</samp> clause.


### Active record

You might have noticed that `\ae\db\select()` and `\ae\db\insert()` functions return results as objects. In addition to exposing column values via their properties, these objects also expose four methods: `values()`, `load()`, `save()`, and `delete()`.

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

$stephenson->name; // 'Neal Stephenson';
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
class Author extends \ae\db\ActiveRecord {
    public static function table() {
        return 'authors';
    }
}
```

The base class implements several static methods that work exactly as corresponding `\ae\db\` functions, just without the first (`$table`) argument:

```php
Author::select([$columns]);
Author::insert($data);
Author::replace($data);
Author::update($data, $predicate);
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
        `published_on` date NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
```

We also need a class to represent this table. The class name is totally arbitrary, so we will call it `Novel`:

```php
class Novel extends \ae\db\ActiveRecord {
    public static function table() {
        return 'books';
    }
}
```

> There are two other methods you can override: `\ae\db\Table::accessor()` to return an array of primary keys; `\ae\db\Table::columns()` to return an array of data columns.

Now we could be adding new books using `Novel::insert()` method directly, but instead we are going to incapsulate this functionality into `Author::add_novel()` method. We will also add `Author::novels()` method for retrieving all novels written by an author:

```php
class Author extends \ae\db\ActiveRecord {
    public static function table() {
        return 'authors';
    }
    
    public function add_novel($title, $year) {
        return Novel::insert([
            'author_id' => $this->id,
            'title' => $title,
            'date' => $year . '-01-01'
        ]);
    }
    
    public function novels() {
        return Novel::select()
            ->where(['author_id' => $this->id]);
    }
}
```

Now let's add a few books to the database:

```php
$gibson->add_novel('Neuromancer', 1984);
$gibson->add_novel('Count Zero', 1986);
$gibson->add_novel('Mona Lisa Overdrive', 1988);

$stephenson->add_novel('Snow Crash', 1992);
$stephenson->add_novel('Cryptonomicon', 1999);
$stephenson->add_novel('Reamde', 2011);

// Note: we don't have to load author's record to add a novel!
$morgan = Author::find($morgan_id);

$morgan->add_novel('Altered Carbon', 2002);
$morgan->add_novel('Broken Angels', 2003);
$morgan->add_novel('Woken Furies', 2005);
```

And finally, let's add a couple of methods to `Novel` class that will return book records sorted alphabetically:

```php
class Novel extends \ae\db\ActiveRecord {
    public static function table() {
        return 'books';
    }
    
    public static function find_all() {
        return self::select()
            ->with('Author', 'author_id', 'author')
            ->order_by('title');
    }
    
    public static function find_recent() {
        return self::find_all()
            ->where('published_on > {date}', [
                'date' => date('Y-m-d', strtotime('-15 years'))
            ]);
    }
}
```

Note how we call `with()` method to add a <samp>LEFT JOIN</samp> clause for <samp>authors</samp> table using <samp>author_id</samp> foreign key, and how we reuse `find_all()` query in `find_recent()` by appending a <samp>WHERE</samp> clause that filters out all novels published more than 15 years ago.

<!-- TODO: Polymorphic relationships, e.g. fk_id/fk_table -->

Let's inventory our recent novel collection:

```php
$novels = Novel::find_recent();
$count = count($novels);

echo "Here are all $count novels ordered alphabetically:\n";

foreach ($novels as $novel) {
    echo "- {$novel->title} by {$novel->author->name}\n";
}
```

Which will output:

```txt
Here are all 4 novels ordered alphabetically:
- Altered Carbon by Richard K. Morgan
- Broken Angels by Richard K. Morgan
- Reamde by Neal Stephenson
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

// Close transaction (rolling back any uncommitted changes)
unset($transaction);
```

It could be that one of your queries fails, which will throw an exception and all uncommitted queries will be rolled back, when the `$transaction` object is destroyed.

> **N.B.** Only one transaction can be open at a time.


### Migrations

Create an initial migration script:

```php
// initial.php
\ae\db\migrate(null, function () {
    \ae\db\query('CREATE TABLE users');
}, function () {
    \ae\db\query('DROP TABLE users');
});
```

Now create another migration script that would make changes to the original:

```php
// latest.php
\ae\db\migrate('path/to/initial.php', function () {
    // forward queries
}, function () {
    // backward queries
});
```

You can migrate to a desired migration like this: 

```php
\ae\db\migrate('path/to/latest.php');
```

Remember, you can use any naming scheme you want.

## Inspector

æ inspector is a builtin development tool you can use to debug and profile your application. It is a tiny web app that requires all requests starting with <samp>/inspector</samp> be mapped to a handler returned by `\ae\inspector\assets()` function:

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


## Utilities

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

If you just need to ensure some (usually global or static) variable is set to its previous state, use `\ae\switch()` to create an object that will do it automatically:

```php
$a = 'foo';

$switch = \ae\switch($a, 'bar');

$a; // 'bar'

unset($switch);

$a; // 'foo'
```

And if you need to run some arbitrary code (to free resources for instance), use `\ae\defer()` to create an object that will do so when it's destroyed:

```php
$file = fopen('some/file', 'r');

$fclose = \ae\defer(function () use ($file) {
    fclose($file);
});
```

### Configuration options

While most æ libraries come with sensible defaults, they also allow you to configure their behavior via `\ae\*\configure()` functions. Internally, all of them use `\ae\options()` function to create an object that:

- enumerates all possible option names
- provides default values for each option
- exposes an array-like interface to get and set values
- ensures that only declared option names are used
- validates value types.

Let's declare a simple set of options:

```php
$options = \ae\options([
    'foo' => true,
    'bar' => [],
    'baz' => null,
]);
```

The options object can be used as a regular associative array:

```php
if (true === $options['foo']) {
    $options['bar'] = [1, 2, 3];
}

$options['baz'] = 'How do you do?';
```

Note that <samp>baz</samp> option can be set to any value type, because its default value is <samp>null</samp>. In contrast, the following code will throw an `\ae\options\Exception`:

```php
try {
    $options['foo'] = null;
} catch (Exception $e) {
    $e->getMessage(); // 'foo can only be TRUE or FALSE'
}
```

You can also set option values by passing an associative array via second argument to `\ae\options()`. Those values are subject to validation rules listed above. This is useful when you want to both declare and use the options object to configure, say, your library:

```php
class MyLibrary {
    protected static $options;
    
    public static function configure(/* $values OR $name, $value*/) {
        $args = func_get_args();
        $values = func_num_args() > 1 ? [ $args[0] => $args[1] ] ? $args[0];
        
        static::$options = \ae\options([
            // options names and their default values
        ], $values);
    }
}
```
