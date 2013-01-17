<?php 

// FIXME: How can I retrieve all submitted values as an array?
// FIXME: Validating, if user tempered with the options, is tedious.

$form = ae::form('form-id');

$form->set_data(array('select' => 'bar'));

if ($form->is_submitted()) 
{
	$form->validate('text', 0) // name, depth
		->required('Please enter some text')
		->max_length(140, 'Let\'s keep it short, shall we?');
		
	$form->validate('textarea');
	
	$form->validate('select') // name, depth = 0
		->options(array('foo','bar'))
		->required('How did you manage to select nothing?!');

	$form->validate('check', 1) // name, depth = 1
		->options(array('foo','bar'))
		->required('Please check both boxes!');
		
	if ($form->is_valid())
	{
		$values = $form->valid_data();
				
		echo '<h1>' . $values['text'] . '</h1>';
		echo '<p>' .  $values['textarea'] . '</p>';
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
		// 	'textarea' => null,
		// 	'select' => '...',
		// 	'check' => array(0 => '...', 1 => null)
		// );
	}
}

// Generate HTML of the form 
?>
<?php $form->open() ?>
<div class="field">
	<label for="text-input">Text input:</label>
	<input name="text" id="text-input" type="text" value="<?php $form->value('text') ?>">
	<?php $form->error('text') ?>
</div>
<div class="field">
	<label for="textarea-input">Text input:</label>
	<textarea name="textarea" id="textarea"><?php $form->value('textarea') ?></textarea>
	<?php $form->error('textarea') ?>
</div>
<div class="field">
	<label for="select-input">Select something:</label>
	<select name="select" id="select-input">
		<option value="foo" <?php $form->selected('select', 'foo') ?>>foo</option>
		<option value="bar" <?php $form->selected('select', 'bar') ?>>bar</option>
	</select>
	<?php $form->error('select') ?>
</div>
<div class="field">
	<label>Checkboxes:</label>
	<label><input type="checkbox" name="check[]" value="foo" <?php $form->checked('check', 'foo') ?>>foo</label>
	<label><input type="checkbox" name="check[]" value="bar" <?php $form->checked('check', 'bar') ?>>bar</label>
	<?php $form->error('check') ?>
</div>
<div class="field">
	<input value="Submit" type="submit">
</div>
<?php $form->close() ?>