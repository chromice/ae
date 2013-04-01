<?php 

$form = ae::form('form-id');

// Set default values
if (!$form->is_posted())
{
	$form->values(array('select' => 'bar'));
}

// Create a single field that accepts integers from 0 to 1000
$number = $form->single('text')
	->required('Please enter some number.')
	->format('Please enter a valid integer number.', aeForm::valid_integer)
	// ->format('Please enter a valid octal number.', aeForm::valid_octal)
	// ->format('Please enter a valid hexadecimal number.', aeForm::valid_hexadecimal)
	// ->format('Please enter a valid decimal number.', aeForm::valid_decimal)
	// ->format('Please enter a valid float number.', aeForm::valid_float)
	// ->format('Please enter a valid number.', aeForm::valid_number)
	->min_value('Cannot be less then zero.', 0)
	->max_value('Cannot be higher then a thousand.', 1000);


// Create a sequence of 0 to 5 fields that accept tweet size chunks of text
$textarea = $form->sequence('textarea', 0, 5) // the last field is validated only if not empty
	->required('Please enter some text.')
	->format('Letters only please.','/^[a-z\s]/i')
	// ->format('Must be a valid email, e.g. "some.dude@example.com".', aeForm::valid_email)
	// ->format('Must be a valid URL with path and query components.', aeForm::valid_url | aeForm::valid_url_path | aeForm::valid_url_query)
	// ->format('Must be a valid IP address.', aeForm::valid_ip)
	// ->format('Must be a valid IP address (IPv4 or IPv6).', aeForm::valid_ipv4 | aeForm::valid_ipv6)
	// ->format('Must be a valid private IP address.', aeForm::valid_pivate_ip)
	// ->format('Must be a valid public IP address.', aeForm::valid_public_ip)
	->min_length('Just type a few characters!', 2)
	->max_length('Let\'s keep it short, shall we?', 140);

// Create a single field, that accepts only 'foo' and 'bar' values
$select = $form->single('select')
	->required('How did you manage to select nothing?!')
	->options(array('foo','bar')); // prevent user from supplying wrong values

// Create a single field, that accepts an array of 'foo' and 'bar' values
$checkboxes = $form->multiple('check') // field is validated only if not empty
	->required('Please check at least one box!') // at least one value is required by default
	->options(array('foo' => 'Foo', 'bar' => 'Bar')); // keys are used here

// Try processing all special actions first
if ($form->value('add'))
{
	$textarea[] = ''; // Empty by default
}
else if ($index = $form->value('remove'))
{
	unset($textarea[$index]);
}
// Process the form only if no other actions are made, and the form is posted
else if ($form->is_posted())
{
	// Run the validation, which will set errors
	$is_valid = $form->validate();
	
	// Custom validation goes here
	if (empty($text->error) && $form->value('number') === '666')
	{
		$text->error = 'Devils are not allowed!';
		
		$is_valid = false;
	}
	
	// If form is valid, do something with it.
	if ($is_valid)
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
}


// Generate HTML of the form 
?>
<?= $form->open() ?>
<!-- <form action="" method="post">
	<input type="hidden" name="__ae_form_id__" value="form-id">
	<input type="submit" tabindex="-1" style="position:absolute;left:-9999px;"> -->
<div class="field">
	<label for="text-input">Number input:</label>
	<input name="<?= $number->name() ?>" id="text-input" type="number" value="<?= $number->value() ?>">
	<?= $number->error('<em class="error">', '</em>') ?>
</div>
<?php foreach ($textarea as $k => $_ta):
	// You could use fields from other sequences in the same loop like this:
	
	// if (empty($sequence[$k]))
	// {
	// 	$sequence[$k] = 'Default value';
	// }
	// 
	// $_other = $sequence[$k];
	// $_another = $another_sequence[$k];
?>
<div class="field">
	<label form="textarea-<?= $_ta->index() ?>">Text area:</label>
	<textarea name="<?= $_ta->name() ?>[<?= $_ta->index() ?>]" id="textarea-<?= $_ta->index() ?>"><?= $_ta->value() ?></textarea>
<?php if ($_ta->index() > 0): ?>
	<button type="submit" name="remove" value="<?= $_ta->index() ?>">Remove</button>
<?php endif ?>
	<?= $_ta->error() ?>
</div>
<?php endforeach ?>
<?php if ($textarea->count() < 5): ?>
<p><button type="submit" name="add" value="textarea">Add</button> textarea.</p>
<?php endif ?>
<div class="field">
	<label for="select-input">Select something:</label>
	<select name="<?= $select->name() ?>" id="select-input">
		<option value="foo" <?= $select->selected('foo') ?>>Foo</option>
		<option value="bar" <?= $select->selected('bar') ?>>Bar</option>
	</select>
	<?= $select->error() ?>
</div>
<div class="field">
	<label>Checkboxes:</label>
	<label><input type="checkbox" name="<?= $checkboxes->name() ?>[]" value="foo" <?= $checkboxes->checked('foo') ?>>foo</label>
	<label><input type="checkbox" name="<?= $checkboxes->name() ?>[]" value="bar" <?= $checkboxes->checked('bar') ?>>bar</label>
	<?= $checkboxes->error() ?>
</div>
<div class="field">
	<button type="submit">Submit</button>
</div>
<?= $form->close() ?>