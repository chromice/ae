<?php

use \ae\Core as ae;

$doc = ae::documentation('/documentation')
	->explains('ae/core.php');

?>

## Hello world

Here is a very a simple æ application:

<?= $example = $doc->example('/001_Hello/index.php') ?>

You should put this code into *index.php* in the root web directory. */ae* directory containing the core and all libraries should be placed there as well. For the request library to work properly you need to instruct Apache to redirect all unresolved URIs to *index.php*, by adding the following rules to *.htaccess* file:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*) index.php?/$1 [L,QSA]
</IfModule>
```

Let's assume the address of this app is *http://localhost/*. If you enter that into the address bar of your web browser, you should see this:

<?= $example->when('/001_Hello/world')->outputs('world.txt') ?>

If you change the address to *http://localhost/universe*, you should see:

<?= $example->when('/001_Hello/universe')->outputs('universe.txt') ?>

Congratulations! You may tinker with the examples (see */examples* directory) or read the rest of this document to get a basic understanding of æ capabilities.

<?php

$doc->save('README.md');