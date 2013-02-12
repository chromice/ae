<?php 

$form = ae::form('form-id')
	->group(array(
		'name',
		'email',
		'permissions'
	), 'user', aeForm::mutliple);

// aeForm::single - all fields are validated normally
// aeForm::multiple - all fields in all groups are validated normally
// aeForm::sequence - only field in non-emtpy groups are validated

$permissions = array(
	'write' => 'Write permission',
	'read' => 'Read permission',
	'share' => 'Share permission'
);

if ($form->is_submitted()) 
{
	$form->validate('name')
		->required('Please specify user name.');

	$form->validate('email')
		->required('Please specify user email.');

	$form->validate('permissions', aeForm::multiple)
		->options($permissions)
		/* required(error message, minimum number, maximum number) */
		->required('Please specify two permissions.', 2, 2); 
		
	if ($form->is_valid())
	{
		$values = $form->valid_data();
		
		echo '<h1>Users</h1>';
		echo '<ul>';
		
		foreach ($values['user'] as $user)
		{
			echo '<li>' . $user['name'] . ' &mdash; ' . $user['email'] . '</li>';
		}
		
		echo "</ul>";
	}
	else
	{
		$errors = $form->errors();
	}
}

?>
<?= $form->open() ?>
<?php while ($form->has('user')): ?>
<fieldset>
	<legend>User</legend>
	<div class="field <?= $form->classes('name') ?>">
		<label for="name-input">Text input:</label>
		<input name="name[<?= $form->current('user') ?>]" id="name-input" type="text" value="<?= $form->value('name') ?>">
		<?= $form->error('name') ?>
	</div>
	<div class="field <?= $form->classes('email') ?>">
		<label for="email-input">Text input:</label>
		<input name="email[<?= $form->current('user') ?>]" id="email-input" type="text" value="<?= $form->value('email') ?>">
		<?= $form->error('email') ?>
	</div>
	<div class="field <?= $form->classes('permissions') ?>">
		<label>Checkboxes:</label>
<?php 	foreach($permissions as $option => $label): ?>
		<label><input type="checkbox" name="permissions[<?= $form->current('user') ?>][]" value="<?= $option ?>" <?= $form->checked('permissions', $option) ?>> <?= $label ?></label>
<?php 	endforeach ?>
		<?= $form->error('permissions') ?>
	</div>
</fieldset>
<?php endwhile ?>
<p><?= $form->add('user', 'Add one more') ?> user.</p>
<div class="field">
	<?= $form->submit('Submit') ?>
</div>
<?= $form->close() ?>