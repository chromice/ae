<?php 

$form = ae::form('form-id');

$form->values(array('select' => 'bar'));

$text = $form->single('text')
	->required('Please enter some text')
	->length('Let\'s keep it short, shall we?', 140);

$textarea = $form->sequence('textarea', 0, 5); // the last field is validated only if not empty

if ($form->value('action') === 'add')
{
	$textarea->increment();
}

$select = $form->single('select')
	->required('How did you manage to select nothing?!')
	->options(array('foo','bar')); // prevent user from supplying wrong values

$checkboxes = $form->multiple('check') // field is validated only if not empty
	->required('Please check at least one box!') // at least one value is required by default
	->options(array('foo' => 'Foo', 'bar' => 'Bar')); // keys are used here
	
if ($form->is_valid())
{
	$values = $form->values();
			
	echo '<h1>' . $values['text'] . '</h1>';
	echo '<p>' . implode('</p><p>', $values['textarea']) . '</p>';
	echo '<dl>';
	echo '<dt>Selected:</dt>';
	echo '<dd>' . $values['select'] . '</dd>';
	echo '<dt>Checked:</dt>';
	echo '<dd>' . implode(', ', $values['checked']) . '</dd>';
	echo '</dl>';
}
else
{
	$errors = $form->errors();
	
	// $errors = array(
	// 	'text' => '...',
	// 	'textarea' => array(...),
	// 	'select' => '...',
	// 	'check' => array(0 => '...', 1 => null)
	// );
}

// Generate HTML of the form 
?>
<?= $form->open() ?>
<div class="field <?= $text->classes() ?>">
	<label for="text-input">Text input:</label>
	<input name="<?= $text->name() ?>" id="text-input" type="text" value="<?= $text->value() ?>">
	<?= $text->error('<em class="error">', '</em>') ?>
</div>
<?php foreach ($textarea as $_ta): ?>
<div class="field <?= $_ta->classes() ?>">
	<label>Text area:</label>
	<textarea name="<?= $_ta->name() ?>[<?= $_ta->index() ?>]"><?= $_ta->value() ?></textarea>
	<?= $_ta->error() ?>
</div>
<?php endforeach ?>
<?php if ($textarea->count() < 5): ?>
<p><button type="submit" name="action" value="add">Add</button> textarea.</p>
<?php endif ?>
<div class="field <?= $select->classes() ?>">
	<label for="select-input">Select something:</label>
	<select name="<?= $select->name() ?>" id="select-input">
		<option value="foo" <?= $select->selected('foo') ?>>Foo</option>
		<option value="bar" <?= $select->selected('bar') ?>>Bar</option>
	</select>
	<?= $select->error() ?>
</div>
<div class="field <?= $checkboxes->classes() ?>">
	<label>Checkboxes:</label>
	<label><input type="checkbox" name="<?= $checkboxes->name() ?>[]" value="foo" <?= $checkboxes->checked('foo') ?>>foo</label>
	<label><input type="checkbox" name="<?= $checkboxes->name() ?>[]" value="bar" <?= $checkboxes->checked('bar') ?>>bar</label>
	<?= $checkboxes->error() ?>
</div>
<div class="field">
	<button type="submit">Submit</button>
</div>
<?= $form->close() ?>