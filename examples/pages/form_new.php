<?php

ae::register('utilities/inspector');

ae::options('inspector')
	->set('dump_context', true);

$form = ae::form_new('example');

/*
	Single group example
*/
$user = $form->group('user');

$avatar = $user->file('avatar', '/uploads/avatars')
	->accept('Please choose .png or .jpeg file as your avatar.', '.png, .jpeg')
	->min_width('{file} is less than 500 pixels wide.', 500)
	->min_height('{file} is less than 500 pixels high.', 500);

$first_name = $user->single('first_name')
	->required('Please enter your first name.', function ($value, $index) {
		return true;
	});

$last_name = $user->single('last_name')
	->required('Please enter your last name.');

$email = $user->single('email')
	->initial('tester@gmail.com')
	->required('Please enter your email address.')
	->valid_pattern('This email address does not seem to be valid.', aeTextValidator::email);

$form->initial(array(
	'user' => array(
		'first_name' => 'Tester',
		'last_name' => 'Numero Uno',
		'terms' => 'on',
	),
	'files' => array(
		'title' => array(
			'This is a gallery title', 'This is another title'
		),
	),
));

$service_options = array(
	'blog' => 'Blog',
	'cms' => 'CMS',
	'shop' => 'Shop',
);
$services = $user->multiple('services')
	->initial(array('shop'))
	->required('Please choose at least two services.', function ($values, $index) {
		return !is_array($values) || count($values) < 2;
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
		return $index === 0;
	});

$images = $files->files('image', '/uploads/gallery')
	->required('You have to upload at least one image.', function ($values, $index) {
		return $index === 0;
	})
	->accept('{file} is not an image.', 'image/*')
	->max_size('{file} is larger than {size}.', 4 * 1024 * 1024)
	->min_width('{file} is less than {width} pixels wide.', 256)
	->min_height('{file} is less than {height} pixels high.', 256);

$descriptions = $files->single('description')
	->min_length('The description must be longer than {length} characters.', 10)
	->max_length('The description must not be longer than {length} characters.', 5000);

$appears_on = $files->multiple('appears_on')
	->required('Please choose a service.')
	->valid_value('You are not using one of these services.', $services->value());


/*
	Validate the form
*/
if ($form->is_submitted() && $form->validate())
{
	echo '<p>The form is submitted and valid!</p>';
	
	// $account = Users::create($user->values())->save();
	
	// foreach ($files->values() as $values)
	// {
	// 	$values['account_id'] = $account->id;
	// 	
	// 	Files::create($values)->save();
	// }
	
	echo '<pre>';
	
	var_export($form->values());
	
	echo '</pre>';
}
else
{
	echo '<p>The form is <strong>invalid</strong>!</p>';
}

?>
<h1>Form</h1>
<?php if ($form->has_errors()): ?>
<h2>Form has errors</h2>
<?php $form->errors() ?>
<?php endif ?>
<?= $form->open() ?> 
<fieldset>
	<legend>Account</legend>
	<div class="field">
		<label for="<?= $avatar->id() ?>">Avatar</label>
		<?= $avatar->input() ?>
		<?= $avatar->error() ?>
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
<?php foreach ($files as $index => $file): ?>
<fieldset>
	<legent>
		Gallery #<?= $index + 1 ?>
		<?= $files->remove_button($index) ?>
	</legent>
	<div class="field">
		<label for="<?= $file['title']->id() ?>">Title</label>
		<?= $file['title']->input('text') ?>
		<?= $file['title']->error() ?>
	</div>
	<div class="field">
		<label for="<?= $file['description']->id() ?>">Description</label>
		<?= $files[$index]['description']->textarea() ?>
		<?= $files[$index]['description']->error() ?>
	</div>
	<div class="field">
		<label for="<?= $file['image']->id() ?>">Images</label>
		<?= $images[$index]->input() ?>
		<?= $images[$index]->error() ?>
	</div>
	<div class="field">
		<span class="label">Appears on</span>
		<?php foreach ($service_options as $value => $label): ?>
		<?= $file['appears_on']->input('checkbox', $value) ?>&nbsp;<label for="<?= $file['appears_on']->id($value) ?>"><?= $label ?></label>
		<?php endforeach ?>
		<?= $file['appears_on']->error() ?>
	</div>
</fieldset>
<?php endforeach ?>
	<?= $files->add_button() ?>
	<button type="submit">Submit</button>
<?= $form->close() ?> 