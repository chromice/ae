# æ – minimalist PHP toolkit

æ (pronounced "ash") is a collection of loosely coupled PHP libraries for all your web development needs: request routing, response caching, templating, form validation, image manipulation, database operations, and easy debugging and profiling.

This project has been created and maintained by its sole author to explore, validate and express his views on web development. As a result, this is an opinionated codebase that attempts to achieve the following goals:

- **Simplicity:** There are no controllers, event emitters and responders, filters, template engines. There are no config files to tinker with, either: all libraries come preconfigured with sensible default values.
- **Reliability**: The APIs were designed to be expressive and error-resistant. The versions of this code have powered a few moderately important websites, which helped iron most kinks out.
- **Performance:** All libraries have been designed with performance and efficiency in mind. Responses can be cached statically and served by Apache alone.
- **Independence:** This toolkit does not have any third-party dependencies, nor does it needlessly adhere to any style guide or standard. There are only 6 thousand lines of code written by a single author, so it would not take you long to figure out what all of them do.

There is nothing particularly groundbreaking or fancy about this toolkit. If you just need a lean PHP framework, you may have found it. However, if someone told you that all your code must be broken into models, views and controllers, you will be better off using something like [Yii](http://www.yiiframework.com) or [Laravel](http://laravel.com). 

æ will be perfect for you, if your definition of a web application falls along these lines:

> A web application is a bunch of scripts thrown together to concatenate a string of text (HTTP response) in response to another string of text (HTTP request).

In other words, æ will not let you forget that most of the back-end programming is a glorified string manipulation, but it will alleviate the most cumbersome aspects of it. 

In more practical terms, if you are putting together a site with a bunch of forms that save data to a database, æ comes with everything you need.

You may still find it useful, even if you are thinking of web app architecture in terms of dispatchers, controllers, events, filters, etc. The author assumes you are working on something complex and wishes you a hearty good luck. ;-)


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

You can download the latest release manually, drop it into your project and `require` <samp>ae/loader.php</samp>:

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

Let's create the most basic of web applications. Put this code into <samp>index.php</samp> in the web root directory:

```php
require 'path/to/ae/loader.php';

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

You can access URI path segments using `\ae\request\path()`  method:

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

While some purist may (somewhat rightfully) disagree with the author's choice of relying on file extensions to distinguish between different file types

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
$files = \ae\request\files(); // returns an associative array of uploaded files (see \ae\file() for more)
```

If you need to access raw request body, use `\ae\request\body()` function:

```php
$post = \ae\request\body(); // same as file_get_contents("php://input")
```


### Request mapping

You should always strive to break down your application into independent components. The best way to handle a request is to map it to a specific function or template that encapsulates part of your application's functionality.

Requests are mapped using rules, which are key-value pairs of path pattern, and either an object that conforms to `\ae\response\Dispatchable` interface or a function that returns such an object.

Here's an example of a request being mapped to a page template:

```php
// GET /about-us HTTP/1.1

\ae\request\map([
    // ...
    '/about-us' => \ae\template('path/to/about-us-page.php'),
    '/our-work' => \ae\template('path/to/our-work-page.php'),
    // ...
]);
```

Or we can write a more generic rule that handles all root level pages by using a placeholder and mapping it to a function:

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

As you can see, we used `{any}` placeholder to catch the page slug and pass its value to our handler function as first argument.

> `{any}` placeholder can match (and capture) a substring within only one path segment, i.e. it can match any character other than <smap>/</smap> (forward slash).

`\ae\template()` will throw an `\ae\path\Exception`, if the template file does not exist, which in turn will result in `\ae\response\error(404)` being dispatched.

> If a request handler throws `\ae\path\Exception`, `\ae\response\error(404)` will be dispatched. If it throws any other exception, `\ae\response\error(500)` will be dispatched instead.

Now, let's assume we want users to be able to download files from a specific directory:

```php
// GET /download/directory/document.pdf HTTP/1.1

\ae\request\map([
    // ...
    '/download' => function ($file_path) {
        return \ae\file('path/to/downloadable/files/' . $file_path);
        // returns '/path/to/downloadable/files/directory/document.pdf' file, if it exists
            ->download(true);
    },
    // ...
]);
```

<!--
    TODO: How do I pass a file though, e.g. show document.pdf, instead of downloading it?
    
    \ae\file()->download(false);
    \ae\file()->download(true);
    \ae\file()->download('custom_name.ext');
-->

First of all, we will take advantage of the fact that `\ae\file()` function returns an object that conforms to `\ae\response\Dispatchable` interface. Secondly, whenever actual matched URI path is longer than the pattern, the remainder of it is past as *the last argument* to our handler. And thirdly, we use `download()` method to set <samp>Content-Disposition</samp> header to <samp>attachment</samp>, and force the download rather than simply display the content of the file.

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

Please note that if the format is wrong, we show 404 error.

<!--
    TODO: How do I make image downloadable?
-->


And finally, our last rule will display home page *or* show 404 error for all unmatched requests by returning `null`:

```php
// GET /about-us HTTP/1.1

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

> Objects returned by `\ae\response()`, `\ae\buffer()`, `\ae\template()`, `\ae\file()`, `\ae\image()` implement `\ae\response\Dispatchable` interface, which allows you to dispatch them. You should refrain from calling `dispatch()` method yourself though, and use the request mapping pattern as much as possible.

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
    ->cache(2 * \ae\cache\minute, \ae\cache\server_side)
    ->dispatch('hello-world.html');

?>
```

When response object is created, it starts buffering all output. Once the `dispatch()` method is called, the buffering stops, HTTP headers are set, and content is output.

> You must explicitly specify the response path when calling `dispatch()` method. To create a response for the current request use `\ae\request\path()` function.

By default all responses are <samp>text/html</samp>, but you can change the type by either setting <samp>Content-type</samp> header to a valid mime-type or appending an appropriate file extension to the dispatched path, e.g. <samp>.html</samp>, <samp>.css</samp>, <samp>.js</samp>, <samp>.json</samp>. 


### Buffering

You can create a buffer and assign it to a variable to start capturing output. All output is captured until the instance is destroyed or buffered content is used:

```php
$buffer = \ae\buffer();

echo "I'm buffered!";

$content = (string) $buffer; // $content === "I'm buffered!"

echo "I'm still buffered!";

unset($buffer);

echo "And I'm not buffered!";
```

> `\ae\buffer()` returns an instance of `\ae\Buffer` class, which implements `__toString()` magic method that always returns a string currently contained in the buffer.


### Templating

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
    'title' => 'Container example'
]); ?>
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

### Caching

If you want your response to be cached client-side for a number of minutes, you should use `cache()` method of the response object. It will set <samp>Cache-Control</samp>, <samp>Last-Modified</samp>, and <samp>Expires</samp> headers for you. If the response is public (i.e. you passed `\ae\cache\server_side` to `cache()` method as the second argument), it will save the response server-side as well.

> Objects returned by `\ae\response()`, `\ae\buffer()`, `\ae\template()`, `\ae\file()`, `\ae\image()` implement `\ae\response\Cacheable` interface, which allows you to cache them.

You can save any response manually using `\ae\cache\save()` function:

```php
\ae\cache\save('hello-world.html', $response, 2 * \ae\cache\hour);
```

You can delete any cached response using `\ae\cache\delete()` function by passing full or partial URL to it:

```php
\ae\cache\delete('hello-world.html');
```

You should also periodically remove all *stale* cache entries via `\ae\cache\clean()`:

```php
\ae\cache\clean();
```

> The garbage collection is a resource-intensive operation, so its usage should be restricted to a cron job.

To completely erase all cached data use `\ae\cache\purge()` function:

```php
\ae\cache\purge();
```

The responses are saved in <samp>cache</samp> directory (in the *web root* directory) by default. For caching to work correctly this directory must exist and be writable. You must also configure Apache to look for cached responses in this directory.

Put the following rules into <samp>.htaccess</samp> file in the *web root* directory:

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

And here are the rules that <samp>cache/.htaccess</samp> must contain:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on

    # If no matching file found, redirect back to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*) /index.php?/$1 [L,QSA]
</IfModule>
```

With everything in place, Apache will first look for a cached response, and only if it finds no valid response, will it route the request to <samp>/index.php</samp>, where your app can generate (and cache statically) a response.


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

The library exposes basic information about the file:

```php
$file = \ae\file('path/to/file.txt');

echo $file->size(); // 12
echo $file->name(); // file.txt
echo $file->type(); // txt
echo $file->mime(); // text/plain

$path = $file->path(); // $path = \ae\path('path/to/file.txt')
```

Existing files can be copied, moved or deleted:

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

Metadata is transient and is never saved to disk, but it may be used by different parts of your application to communicate useful information.


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

### Resizing and cropping

You can transform the image by changing its dimensions in 4 different ways:

- `scale($w, $h)` scales the image in one or both dimensions; use `null` for either dimension to scale proportionally
- `crop($w, $h)` crops the image to specified dimensions, even if it is smaller than target dimensions
- `fit($w, $h)` scales the image, so it fits into a box defined by target dimensions
- `fill($w, $h)` scales and crops the image, so it completely covers a box defined by target dimensions.

You will rarely need to use the first two methods, as the the latter two cover most of use cases:

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

You can specify the point of origin using `align()` method before you apply `crop()` and `fill()` transformations to an image:

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


### Applying filters

You can also apply one or more filters:

```php
$thumbnail
    ->blur()
    ->colorize(0.75, 0, 0)
    ->save();
```

Here are all the filters exposed by the library:

- `blur()` blurs the image using the Gaussian method
- `brightness($value)` changes the brightness of the image; accepts a number from <samp>-1.0</samp> to <samp>1.0</samp>
- `contast($value)` changes the contrast of the image; accepts a number from <samp>-1.0</samp> to <samp>1.0</samp>
- `colorize($red, $green, $blue)` changes the contrast of the image; accepts numbers from <samp>0.0</samp> to <samp>1.0</samp>
- `grayscale()` converts the image into grayscale
- `negate()` reverses all colors of the image
- `pixelate($size)` applies pixelation effect to the image
- `smooth($value)` makes the image smoother

### Converting and saving

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

Form library lets you create web forms and validate the both client- and server-side using HTML5 constraints. Both forms and individual controls implement `__toString()` magic method and can render themselves into valid HTML when cast to string. You can render a form manually, of course, if you so desire.

### Declaring

You can create a new form using `\ae\form()` function. It takes a form name (unique within the context of a single web page) as the first argument, and (optionally) an array of default field values as the second argument:

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

$form['phone'] = \ae\form\text('Phone number'); 
// NB! Never use \ae\form\number() for phone numbers!

$form['phone_type'] = \ae\form\radio('Is home/work/mobile number?', 'home')
    ->options([
        'home' => 'Home number',
        'work' => 'Work number',
        'mobile' => 'Mobile number',
    ]);

$form['birth_date'] = \ae\form\date('Birthday date')
    ->max_value('-18 years', 'You must be at least 18 years old!');

$form['photos'] = \ae\form\file('Photos of you')
    ->multiple()
    ->accept('.jpg,.png,.gif')
    ->min_width(200)
    ->min_height(200)
    ->max_size(10 * \ae\file\megabyte);
```

### Validating

Once the form and its fields are declared, you can check if it has been submitted, and if the submitted values are valid:

```php
if ($form->is_submitted() && $form->is_valid())
{
    // All HTML5 constraints are met
    // Time for custom validation
    $is_valid = true;
    
    // Validate name
    if ($form['name']->value === 'John Connor')
    {
        $form['name']->error = 'You are not John Connor! Enter your real name please.';
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


### Displaying

In order to render the form into HTML you can simply cast it to a string:

```php
echo $form;
```

Alternatively, you can render the form by manually calling `open()` and `close()` methods to create `<form>` and `</form>` tags, and iterating through all fields and rendering them individually:

```php
<?= $form->open(['novalidate' => true]) ?>

<?php foreach ($form as $field): ?>
    <div class="field <?= $field->classes() ?>">
        <?= $field->label() ?>
        <?= $field->control(['placeholder' => 'Enter ' . $field->label]); ?>
        <?= $field->error() ?>
    </div>
<?php endforeach ?>

<?= $form->close() ?>
```



## Database

Database library lets you make queries to a MySQL database, and exposes simple object-oriented abstractions for individual tables and records.

You must to provide connection parameters first:

```php
\ae\db\configure([
    'host'     => 'localhost'
    'user'     => 'root'
    'password' => 'root'
    'database' => 'ae_db'
]);
```

Provided the connection parameters are correct and the database (<samp>ae_db</samp> in this example) exists, we can try to make a query to it:

```php
try {
    \ae\db\query("SELECT 1");
} catch (\ae\db\Exception $e) {
    echo 'Something went wrong: ' . $e->getMessage();
}
```

As you can see, whenever something goes wrong, `\ae\db\Exception` exception is thrown. 

If you want to know what queries were made and how much memory and time they took, you should turn query logging on:

```php
\ae\inspector\show('queries', true);
```

> You must show inspector before you start making queries! See [Inspector](#inspector) section for more information.


### Making queries 

Let's first create <samp>authors</samp> table using `\ae\db\query()` function:

```php
\ae\db\query("CREATE TABLE IF NOT EXISTS `authors` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `nationality` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
```

Let's fill this table with some data:

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

Now, there's a typo in the authors name, so let's fix it:

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

Here we used `{data:set}` placeholder and specified its value the third argument. We also used three parameters in the query and specified their values via the second argument.

**NB!** You should always supply parameter and data values via the second and third arguments to prevent potential SQL injection attacks!

And if we run this query:

```php
$result = \ae\db\query("SELECT * FROM `authors` WHERE `author_id` = {id}", [
        'id' => $morgan_id
    ]);

echo $result[0]->name . ' is ' . $result[0]->nationality;
```

It should produce this string:

```txt
Richard K. Morgan is British
```


### Specialized functions 

You can make any query via `\ae\db\query()` function alone, but it's not as convenient as the specialized functions:

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
> A predicate can be either an associative array of column/value pairs, or an object returned by `\ae\db\predicate()` function, e.g. `\ae\db\predicate('a_column LIKE "%{value}%"', ['value' => 'foo'])`.

Let's add more data:

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

- `join($table, $on_predicate)` - adds a <samp>JOIN</samp> clause; accepts table name as the first argument, and an associative array of foreign key/primary key pairs as the second argument.
- `where($predicate)` or `where($template, $parameters)` – adds a <samp>WHERE</samp> clause using a predicate object or by creating a predicate from template and parameters; multiple clauses are concatenated using <samp>AND</samp> operator.
- `group_by($column)` or `group_by($columns)` – adds a <samp>GROUP BY</samp> clause; accepts a column name or an array of column names.
- `having($predicate)` or `having($template, $parameters)` – adds a <samp>HAVING</samp> clause.
- `order_by($column[, $order])` or `order_by($columns_order)` – adds an <samp>ORDER BY</samp> clause; accepts a column name and an optional sort direction (`\ae\db\ascending` or `\ae\db\descending`), or an associative array of column/sort direction pairs.
- `limit($limit[, $offset])` – add a <samp>LIMIT</samp> clause.


### Active record

You might have noticed that `\ae\db\select()` and `\ae\db\insert()` functions return results as objects. In addition to giving access to column values via corresponding properties, these objects expose three methods: `load()`, `save()`, and `delete()`.

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

Now, Shakespeare was a playwright, while the rest of the authors are novelists. So let's delete his record:

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

We also need a class to represent this table. The class name is totally arbitrary, so we will name it `Novel`:

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

Most of this code should be familiar to you. The only new thing here is `with()` method which joins a record from <samp>authors</samp> table using <samp>author_id</samp> foreign key.

> We could manually specify the foreign key name via the second argument, and the property name via the third, e.g. `->with('Author', 'author_id', 'author')`, but they are automatically derived from class name.

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

A sequence of interdependent database queries must always be wrapped in a transaction to prevent race condition and ensure data integrity:

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

This way, if one of your SQL queries fails, it will throw an exception and all uncommitted queries will be rolled back, when the `$transaction` object is destroyed.

**NB!** Only one transaction can exist at a time.


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

By default, all paths are resolved relative to the location of your main script. Buy you are encouraged to explicitly specify the root directory:

```php
\ae\path\configure('root', '/some/absolute/path');

$absolute_path = \ae\path('relative/path/to/file.ext');
```

A part of your application may need to resolve path relative to its own directory. In this case, instead of changing the configuration back and forth (which is very error prone), you should save the path to that directory to a variable:

```php
$dir = \ae\path('some/dir');

$file = $dir->path('filename.ext'); // same as \ae\path('some/dir/filename.ext');
```

`\ae\path()` function and `path()` method always returns an object, but you must explicitly cast it to string, when you need one:

```php
$path_string = (string) $path_object;
```


### Exception safety

æ is designed with strong exception safety in mind. You make you code exception-safe too by taking advantage of the object life cycle. 

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

While most æ libraries come with sensible defaults, they also allow you to configure their behavior via `\ae\*\configure()` function. Internally, all of them use `\ae\options()` function to create an object that:

1. enumerates all possible option names
2. provides default values for each option
3. exposes an array-like interface to get and set values
4. ensures that only declared option names can be used
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


