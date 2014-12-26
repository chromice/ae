<?php

/// include 'path/to/ae/core.php'

$route = ae::request()->route(array(
	// 1. Map account to /responders/account.php script
	'/account' => __DIR__ . '/responders/account.php',
	
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
	'/' => __DIR__ . '/responders/pages'
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
