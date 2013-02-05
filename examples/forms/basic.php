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
<?php $form->open() ?>
<div class="field <?php $form->classes('text') ?>">
	<label for="text-input">Text input:</label>
	<input name="text" id="text-input" type="text" value="<?php $form->value('text') ?>">
	<?php $form->error('text') ?>
</div>
<?php while ($form->has('textarea', 0)): ?>
<div class="field <?php $form->classes('textarea') ?>">
	<label for="textarea-input">Text area:</label>
	<?php $form->error('textarea') ?>
	<textarea name="textarea" id="textarea"><?php $form->value('textarea') ?></textarea>
</div>
<?php endwhile ?>
<p><?php $form->add('textarea', 'Add', 'Add one more') ?> text area.</p>
<div class="field <?php $form->classes('select') ?>">
	<label for="select-input">Select something:</label>
	<select name="select" id="select-input">
		<option value="foo" <?php $form->selected('select', 'foo') ?>>Foo</option>
		<option value="bar" <?php $form->selected('select', 'bar') ?>>Bar</option>
	</select>
	<?php $form->error('select') ?>
</div>
<div class="field <?php $form->classes('check') ?>">
	<label>Checkboxes:</label>
	<label><input type="checkbox" name="check[]" value="foo" <?php $form->checked('check', 'foo') ?>>foo</label>
	<label><input type="checkbox" name="check[]" value="bar" <?php $form->checked('check', 'bar') ?>>bar</label>
	<?php $form->error('check') ?>
</div>
<div class="field">
	<?php $form->submit('Submit') ?>
</div>
<?php $form->close() ?>