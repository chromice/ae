<?php 

ae::register('utilities/inspector');

ae::options('inspector')
	->set('dump_context', true);

$form = ae::form('form-id');

// Set default values
if (!$form->is_submitted())
{
	$form->values(array(
		'number' => '1',
		'select' => 'bar',
		// 'textarea' => array(123, 124)
	));
}

// Create a single field that accepts integers from 0 to 1000
$number = $form->single('number')
	->required('Please enter some number.')
	->valid_pattern('Please enter a valid integer number.', aeValidator::integer)
	->valid_value('Devils are not allowed!', function ($value, $index) {
		return $value !== '666';
	})
	->min_value('Cannot be less then zero.', 0)
	->max_value('Cannot be higher then a thousand.', 1000);

$file = $form->file('single_file', '/uploads')
	->required('Please choose a file to upload.');
$gallery = $form->files('gallery', '/uploads')
	->required('Please choose one or more images to upload.')
	->accept('{name} is not an image.', 'image/*');

// Create a sequence of 0 to 5 fields that accept tweet size chunks of text
$textarea = $form->sequence('textarea', 1, 5) // the last field is validated only if not empty
	->valid_value('Second box must contain 555.', function ($value, $index) {
		return $index != 1 || $value == 555;
	})
	->min_length('Just type a few characters!', 2)
	->max_length('Let\'s keep it short, shall we?', 140);
	
$textarea->required('Please enter some text.', function ($index) use ($textarea) {
	return $textarea->count() - 1 > $index || $index === 0;
});

// Create a single field, that accepts only 'foo' and 'bar' values
$select = $form->single('select')
	->required('How did you manage to select nothing?!')
	->valid_value('Wrong option selected.', array('foo','bar')); // prevent user from supplying wrong values

// Create a single field, that accepts an array of 'foo' and 'bar' values
$checkboxes = $form->multiple('check') // field is validated only if not empty
	->required('Please check at least one box!') // at least one value is required by default
	->valid_value('Wrong option selected.', array('foo', 'bar')); // keys are used here

// Try processing all special actions first
if ($form->value('add'))
{
	$textarea[] = ''; // Empty by default
}
elseif ($index = $form->value('remove')) // NB! intentionally does not work for 0.
{
	unset($textarea[$index]);
}
// Process the form only if no other actions are made, and the form is posted
elseif ($form->is_submitted())
{
	// Run the validation, which will set errors
	$is_valid = $form->validate();
	$file->upload('/uploads');
	$gallery->upload('/uploads');
	
	// If form is valid, do something with it.
	if ($is_valid)
	{
		$values = $form->values();

		echo '<h1>' . $values['number'] . '</h1>';
		echo '<p>' . implode('</p><p>', $values['textarea']) . '</p>';
		echo '<dl>';
		echo '<dt>Selected:</dt>';
		echo '<dd>' . $values['select'] . '</dd>';
		echo '<dt>Checked:</dt>';
		echo '<dd>' . implode(', ', $values['check']) . '</dd>';
		echo '</dl>';
		
		ae::log('Form values:', $values);
	}
	else
	{
		$errors = $form->errors();
		
		// $errors = array(
		// 	'number' => '...',
		// 	'textarea' => array(...),
		// 	'select' => '...',
		// 	'check' => array(0 => '...', 1 => null)
		// );
	}
}


// Generate HTML of the form 

// ===========================================
// = NB! <button> element is bugged in IE6/7 =
// = and does not post its name/value at all =
// ===========================================
?>
<?= $form->open() ?>
<!-- <form action="" method="post">
	<input type="hidden" name="__ae_form_id__" value="form-id">
	<input type="hidden" name="__ae_form_nonce__" value="1">
	<input type="submit" tabindex="-1" style="position:absolute;left:-9999px;"> -->
<div class="field">
	<label for="<?= $number->id() ?>">Number input:</label>
	<?= $form['number']->input('text') ?>
	<?= $form['number']->error('<em class="error">', '</em>') ?>
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
	<label for="<?= $_ta->id() ?>">Text area <?= $_ta->index() + 1 ?>:</label>
	<?= $_ta->textarea() ?>
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
	<label for="<?= $select->id() ?>">Select something:</label>
	<?= $select->select(array(
		'foo' => 'Foo',
		'bar' => 'Bar'
	)) ?>
	<?= $select->error() ?>
</div>
<div class="field">
	<label>Checkboxes:</label>
	<label><?= $checkboxes->input('checkbox', 'foo') ?>foo</label>
	<label><?= $checkboxes->input('checkbox', 'bar') ?>bar</label>
	<?= $checkboxes->error() ?>
</div>
<div class="field">
	<label for="<?= $file->id() ?>">Single file:</label>
	<?= $file->input(); ?>
	<?= $file->error(); ?>
</div>
<div class="field">
	<label for="<?= $gallery->id() ?>">Gallery:</label>
	<?= $gallery->input(); ?>
	<?= $gallery->error(); ?>
</div>
<div class="field">
	<button type="submit">Submit</button>
</div>
<?= $form->close() ?>
