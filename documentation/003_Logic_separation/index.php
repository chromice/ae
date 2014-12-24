<?php

// ==================
// = Handle request =
// ==================
$filters = array(
    'offset' => !empty($_GET['offset']) ? (int) $_GET['offset'] : 0,
    'total' => !empty($_GET['total']) ? (int) $_GET['total'] : 100
);
$filters = array_map($filters, function ($value) {
	return max($value, 0);
});


// =============================
// = Operate on internal state =
// =============================
$results = get_results($filters);


// =====================
// = Generate response =
// =====================
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