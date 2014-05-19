<?php

ae::register('utilities/inspector');

ae::options('inspector')
	->set('dump_context', true);

$form = ae::form_new('example');

/*
	Single group example
*/
$user = $form->group('user');

$avatar = $user->files('avatar', '/uploads/avatars')
	->accept('Please choose .png or .jpeg file as your avatar.', '.png, .jpeg')
	->min_width('{file} is less than 500 pixels wide.', 500)
	->min_height('{file} is less than 500 pixels high.', 500);

$first_name = $user->single('first_name')
	->required('Please enter your first name.', function ($value, $index) {
		// $index is always NULL for grouped fields
		return !empty($value);
	});

$last_name = $user->single('last_name')
	->required('Please enter your last name.');

$email = $user->single('email')
	->required('Please enter your email address.')
	->valid_pattern('This email address does not seem to be valid.', aeTextValidator::email);

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

$images = $files->files('image', '/uploads/gallery')
	->required('You have to upload at least one image.', function ($values, $index) {
		return $index > 0 || is_array($values) && count($values) > 0;
	})
	->accept('{file} is not an image.', 'images/*')
	->max_size('{file} is larger than 4 megabytes.', 4 * 1024 * 1024)
	->min_width('{file} is less than 256 pixels wide.', 256)
	->min_height('{file} is less than 256 pixels high.', 256);

$descriptions = $files->single('description')
	->min_length('The description must be longer than {length} characters.', 10)
	->max_length('The description must not be longer than {length} characters.', 5000);

$appears_on = $files->multiple('appears_on')
	->required('Please choose a service.')
	->valid_value('You are not using one of these services.', $services->value());

if ($form->is_submitted() && isset($_POST['add_file']))
{
	$files->add();
}

if ($form->is_submitted() && !empty($_POST['remove_file']) && is_numeric($_POST['remove_file']))
{
	$files->remove($_POST['remove_file']);
}

/*
	Validate the form
*/
if ($form->is_submitted() && !$form->validate())
{
	echo '<p>The form is submitted and valid!</p>';
	
	// $account = Users::create($user->values())->save();
	
	// foreach ($files->values() as $values)
	// {
	// 	$values['account_id'] = $account->id;
	// 	
	// 	Files::create($values)->save();
	// }
}

?>
<?php if ($form->is_submitted()): ?>
<h1>Values</h1>
<?php var_dump($form->values()) ?>
<h1>Errors</h1>
<?php var_dump($form->errors()) ?>
<?php endif ?>
<h1>Form</h1>
<?= $form->open() ?> 
<fieldset>
	<legend>Account</legend>
	<div class="field">
		<label for="<?= $avatar->id() ?>">Avatar</label>
		<?= $avatar->input() ?>
		<?= $avatar->errors() ?>
	</div>
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
		<?= $services->error() ?>
	</div>
	<div class="field">
		<p><?= $terms->input('checkbox') ?>&nbsp;<label for="<?= $terms->id() ?>">I hereby agree to whatever Terms &amp; Conditions.</label></p>
		<?= $terms->error() ?>
	</div>
</fieldset>
<?php foreach ($files as $index => $file): ae::log('file', $file); ?>
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
		<label for="<?= $file['image']->id() ?>">Images</label>
		<?= $file['image']->input() ?>
		<?= $file['image']->errors() ?>
	</div>
	<div class="field">
		<span class="label">Appears on</span>
		<?php foreach ($service_options as $value => $label): ?>
		<?= $file['appears_on']->input('checkbox', $value) ?>&nbsp;<label for="<?= $file['appears_on']->id($value) ?>"><?= $label ?></label>
		<?php endforeach ?>
	</div>
</fieldset>
<?php endforeach ?>
<?php if (count($files) < $files->max()): ?>
	<!-- <button type="submit" name="__ae_form_action__[add][files]" value="0">Add another gallery</button> -->
<?php endif ?>
	<button type="submit">Submit</button>
<?= $form->close() ?> 