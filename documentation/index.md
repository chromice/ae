
## Hello world

Let's create the most basic web application. Put this code into *index.php* in the web root directory:


```php
<?php

include 'path/to/core.php';

use \ae\Core as ae;

echo 'Hello ' . ae::request()->segment(0, "world") . '!';

?>
```

You should also instruct Apache to redirect all unresolved URIs to *index.php*, by adding the following rules to *.htaccess* file:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Now, if you open our app – located at, say, *http://localhost/* – in a browser you should see this:


```txt
Hello world!
```

If you change the address to *http://localhost/universe*, you should see:


```txt
Hello universe!
```

Congratulations!

