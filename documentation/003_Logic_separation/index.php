<?php

// ==================
// = Business logic =
// ==================

// Process input
$filters = array(
    'offset' => !empty($_GET['offset']) ? (int) $_GET['offset'] : 0,
    'total' => !empty($_GET['total']) ? (int) $_GET['total'] : 100
);

// Execute business logic
$results = get_results($filters);

// ======================
// = Presentation logic =
// ======================
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