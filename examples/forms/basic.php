<?php 

$form = ae::form('form-id');

$form->set_data(array('select' => 'bar'));

if ($form->is_submitted()) 
{
	$form->validate('text')
		->required('Please enter some text')
		->max_length(140, 'Let\'s keep it short, shall we?');
		
	$form->validate('textarea', aeForm::sequence); // the last field is validated only if not empty
	
	$form->validate('select', aeForm::single)
		->options(array('foo','bar')) // prevent user from supplying wrong values
		->required('How did you manage to select nothing?!');
	
	$form->validate('check', aeForm::multiple) // field is validated only if not empty
		->options(array('foo' => 'Foo', 'bar' => 'Bar')) // keys are used here
		->required('Please check at least one box!'); // at least one value is required by default
		
	if ($form->is_valid())
	{
		$values = $form->valid_data();
				
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
}

// Generate HTML of the form 
?>
<?= $form->open() ?>
<div class="field <?= $form->classes('text') ?>">
	<label for="text-input">Text input:</label>
	<input name="text" id="text-input" type="text" value="<?= $form->value('text') ?>">
	<?= $form->error('text') ?>
</div>
<?php while ($form->has('textarea', 0)): ?>
<div class="field <?= $form->classes('textarea') ?>">
	<label for="textarea-input">Text area:</label>
	<textarea name="textarea[<?= $form->current('textarea') ?>]" id="textarea"><?= $form->value('textarea') ?></textarea>
	<?= $form->error('textarea') ?>
</div>
<?php endwhile ?>
<p><?= $form->add('textarea', 'Add', 'Add one more') ?> text area.</p>
<div class="field <?= $form->classes('select') ?>">
	<label for="select-input">Select something:</label>
	<select name="select" id="select-input">
		<option value="foo" <?= $form->selected('select', 'foo') ?>>Foo</option>
		<option value="bar" <?= $form->selected('select', 'bar') ?>>Bar</option>
	</select>
	<?= $form->error('select') ?>
</div>
<div class="field <?= $form->classes('check') ?>">
	<label>Checkboxes:</label>
	<label><input type="checkbox" name="check[]" value="foo" <?= $form->checked('check', 'foo') ?>>foo</label>
	<label><input type="checkbox" name="check[]" value="bar" <?= $form->checked('check', 'bar') ?>>bar</label>
	<?= $form->error('check') ?>
</div>
<div class="field">
	<?= $form->submit('Submit') ?>
</div>
<?= $form->close() ?>