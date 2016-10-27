# Testing 

README.md is an executable specification. Each chapter is self-contained and artifacts of running one example can be used in all subsequent examples within that chapter. All example code is run, even examples hidden from Markdown renderer in HTML comments. In fact, all code that is not required to understand how somethings works, but is necessary for the example code to be executed properly should be hidden.

In many cases a single example listing can specify both its assumptions (input parameters) and assertions (success state). For example:

```php
// GET /?foo=bar HTTP/1.1

$_GET['foo']; // 'bar'
```

The test script will interpret it loosely as follows:

```php
$_GET['foo'] = 'bar';

if ($_GET['foo'] !== 'bar') fail();
```

## Assumptions

You can mock a <samp>GET</samp> or <samp>POST</samp> request by specifying a commented out HTTP request header at the beginning of your PHP example:

```php
// POST /form.php HTTP/1.1
// Host: www.example.com
// 
// input=value&button=submit
```

Alternatively you can specify HTTP header separtely right before the PHP example it belongs to:

```http
GET /index.html HTTP/1.1
Host: www.example.com
```

<!-- TODO: Implement mocking a request: http://phpdbg.com/docs/mocking-webserver -->

## Assertions

You can make assertions about the expected state of variables, expected errors and exceptions:

```php
// Assertions about types:
$foo; // bool
$foo; // int
$foo; // float
$foo; // string
$foo; // array
$foo; // object
$foo; // callable
$foo; // resource
$foo; // null

// Assertions about scalar values:
$scalar; // true
$scalar; // false
$scalar; // 123
$scalar; // 123.00
$scalar; // 'test string'

// Assertions about arrays:
$array; // ['a', 'b', 'c']
$array; // ['a', 'b', ...]
$array; // ['a' => ..., 'b', 'c']

// Assertions about objects:
$object; // ClassOrInterfaceName

// Assertions about exceptions:
funcOrMethod(); // throws ExceptionClass('Optional Error string')

// Assertions about errors, warnings and notices:
funcOrMethod(); // triggers 'Error, warning or notice message string'
```

You can also make assertions about expected output:

```php
echo 'Hello World!';
```

```txt
Hello World!
```

## Dependencies 

Some scripts may depend on other scripts or files. You can specify such dependencies by naming the file (e.g. <samp>hello_world.php</samp>) in paragraph preceeding the listing of its content and ending the paragraph with a colon:

```php
echo 'Hello. World.';
```

Then you can reference that dependency by prepending its name with `'path/to/'`, e.g.:

```php
echo 'Say: ';

include 'path/to/hello_world.php';
```

```txt
Say: Hello. World.
```
