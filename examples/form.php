<?php 

$form = ae::form('form-id');

$form->values(array('select' => 'bar'));

$text = $form->field('text')
	->required('Please enter some text')
	->length('Let\'s keep it short, shall we?', 140);
	
$textareas = $form->sequence('textarea', 0, 5); // the last field is validated only if not empty

$select = $form->field('select')
	->options(array('foo','bar')) // prevent user from supplying wrong values
	->required('How did you manage to select nothing?!');

$checkboxes = $form->multiple('check') // field is validated only if not empty
	->options(array('foo' => 'Foo', 'bar' => 'Bar')) // keys are used here
	->required('Please check at least one box!'); // at least one value is required by default
	
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
	<input name="text" id="text-input" type="text" value="<?= $text->value() ?>">
	<?= $text->error('<em class="error">', '</em>') ?>
</div>
<?php while ($textarea = $textareas->next()): ?>
<div class="field <?= $textarea>classes() ?>">
	<label for="textarea-input">Text area:</label>
	<textarea name="textarea[<?= $textarea->index() ?>]" id="textarea"><?= $textarea->value() ?></textarea>
	<?= $textarea->error() ?>
</div>
<?php endwhile ?>
<?php if ($textareas->count() < 5): ?>
<p><?= $form->add('textarea', 'Add', 'Add one more') ?> text area.</p>
<?php endif ?>
<div class="field <?= $select->classes() ?>">
	<label for="select-input">Select something:</label>
	<select name="select" id="select-input">
		<option value="foo" <?= $select->selected('foo') ?>>Foo</option>
		<option value="bar" <?= $select->selected('bar') ?>>Bar</option>
	</select>
	<?= $select->error() ?>
</div>
<div class="field <?= $checkboxes->classes() ?>">
	<label>Checkboxes:</label>
	<label><input type="checkbox" name="check[]" value="foo" <?= $checkboxes->checked('foo') ?>>foo</label>
	<label><input type="checkbox" name="check[]" value="bar" <?= $checkboxes->checked('bar') ?>>bar</label>
	<?= $checkboxes->error() ?>
</div>
<div class="field">
	<?= $form->submit('Submit') ?>
</div>
<?= $form->close() ?>