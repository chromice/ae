<?php

$glossary = [
    'Ash' => 'a tree with compound leaves, winged fruits, and hard pale timber, widely distributed throughout north temperate regions.',
    'Framework' => 'an essential supporting structure of a building, vehicle, or object.',
    'PHP' => 'a server-side scripting language designed for web development but also used as a general-purpose programming language.'
];

echo ae::snippet(__DIR__ . '/snippet.php', [
    'data' => $glossary
]);