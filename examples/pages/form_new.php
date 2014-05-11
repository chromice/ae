<?php

$form = ae::form('example');

/*
	Single group example
*/
$user = $form->group('user');

$first_name = $user->single('first_name')
	->required('Please enter your first name.', function ($value, $index) {
		// $index is always NULL for grouped fields
		return !empty($value);
	});

$last_name = $user->single('last_name')
	->required('Please enter your last name.');

$email = $user->single('email')
	->required('Please enter your email address.')
	->valid_pattern('This email address does not seem to be valid.', aeValidator::email);

$service_options = array(
	'blog' => 'Blog',
	'cms' => 'CMS',
	'shop' => 'Shop',
);
$services = $user->multiple('services')
	->required('Please choose at least two services.', function ($values, $index) {
		return is_array($values) && count($values) > 1;
	})
	->valid_value('Unknown service selected.', array_keys($service_options));

$terms = $user->single('terms')
	->required('You have to agree to the terms to proceed.');

/*
	Sequence example
*/
$files = $form->sequence('files', 1, null); // One or more sequence elements

$titles = $files->single('title')
	->required('Please enter the gallery title.', function ($value, $index) {
		return $index > 0 || !empty($value);
	});

$images = $files->files('image')
	->required('You have to upload at least one image.', function ($values, $index) {
		return $index > 0 || is_array($values) && count($values) > 0;
	})
	->max_size('{file} is larger than {size}.', 4 * 1024 * 1024)
	->accept('{file} is not an image.', 'images/*');

$descriptions = $files->single('description')
	->min_length('The description must be longer than {length} characters.', 10)
	->max_length('The description must not be longer than {length} characters.', 5000);

/*
	Validate the form
*/
if ($form->is_submitted() && $form->is_valid())
{
	$account = Users::create($user->values())->save();
	
	foreach ($files->values() as $values)
	{
		$values['account_id'] = $account->id;
		
		Files::create($values)->save();
	}
}

?>
<?= $form->open() ?> 
<fieldset>
	<legend>Account</legend>
	<div class="field">
		<label for="<?= $first_name->id() ?>">First&nbsp;name</label>
		<?= $first_name->input('text') ?>
		<?= $first_name->error() ?>
	</div>
	<div class="field">
		<label for="<?= $last_name->id() ?>">Last&nbsp;name</label>
		<?= $last_name->input('text') ?>
		<?= $last_name->error() ?>
	</div>
	<div class="field">
		<label for="<?= $email->id() ?>">Email</label>
		<?= $email->input('email') ?>
		<?= $email->error() ?>
	</div>
	<div class="field">
		<span class="label">Services</span>
		<?php foreach ($service_options as $value => $label): ?>
		<?= $services->input('checkbox', $value) ?>&nbsp;<label for="<?= $services->id($value) ?>"><?= $label ?></label>
		<?php endforeach ?>
	</div>
	<div class="field">
		<p><?= $terms->input('checkbox') ?>&nbsp;<label for="<?= $terms->id() ?>">I hereby agree to whatever Terms &amp; Conditions.</label></p>
		<?= $terms->error() ?>
	</div>
</fieldset>
<?php foreach ($files as $index => $file): ?>
<fieldset>
	<legent>
		Gallery #<?= $index + 1 ?>
		<!-- <button type="submit" name="__ae_form_action__[remove][files]" value="<?= $index ?>">Remove</button> -->
	</legent>
	<div class="field">
		<label for="<?= $file['title']->id() ?>">Title</label>
		<?= $file['title']->input('text') ?>
		<?= $file['title']->error() ?>
	</div>
	<div class="field">
		<label for="<?= $file['description']->id() ?>">Description</label>
		<?= $file['description']->textarea() ?>
		<?= $file['description']->error() ?>
	</div>
	<div class="field">
		<label for="<?= $images[$index]->id() ?>">Images</label>
		<?= $images[$index]->input() ?>
		<?= $images[$index]->error() ?>
	</div>
</fieldset>
<?php endforeach ?>
<?php if (count($files) < $files->max()): ?>
	<!-- <button type="submit" name="__ae_form_action__[add][files]" value="0">Add another gallery</button> -->
<?php endif ?>
<button type="submit">Submit</button>
<?= $form->close() ?> 