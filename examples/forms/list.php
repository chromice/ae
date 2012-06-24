<?php

// Create form
$form = ae::form('list');

$items_count = $form->value('items_count', 2);

if ($form->value('add-form-item', false))
{
	$items_count++;
}

for ($i=0; $i < $item_count; $i++)
{ 
	$form['item'][] = $form::text('Item')->required();
}

if ($id = $form->value('delete-form-item', false))
{
	unset($form['item'][$id]);
}

if ($form->submitted()) // if submit button was used...
{
	// ...do something with validated data.
	
	echo "<h1>Submitted data:</h1>\n";
	echo "<ul>\n";
	
	$values = $form->values();
	
	foreach ($values['item'] as $key => $value)
	{
		echo '<li>' . $value . "</li>\n";
	}
	
	echo "</ul>\n";
}


?>
<?= $form->open(array(
	'items_count' => $items_count
)) ?>
<ul>
<?php foreach ($form['item'] as $i => $field): ?>
	<li><?= $field ?> <?= $form->button('delete-form-item', $i, 'Delete', array(
		'class' => 'delete'
	)) ?></li>
<?php endforeach ?>
	<div class="field button">
		<?= $form->button('add-form-item', 1, 'Add') ?>
		<?= $form->submit('Save') ?>
	</div>
</ul>
<?= $form->close() ?>