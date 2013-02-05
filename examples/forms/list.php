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
<?php $form->open() ?>
<?php while ($form->has('user')): ?>
<fieldset>
	<legend>User #123</legend>
	<div class="field <?php $form->classes('name') ?>">
		<label for="text-input">Text input:</label>
		<input name="text" id="text-input" type="text" value="<?php $form->value('name') ?>">
		<?php $form->error('name') ?>
	</div>
	<div class="field <?php $form->classes('email') ?>">
		<label for="text-input">Text input:</label>
		<input name="text" id="text-input" type="text" value="<?php $form->value('email') ?>">
		<?php $form->error('email') ?>
	</div>
	<div class="field <?php $form->classes('permissions') ?>">
		<label>Checkboxes:</label>
<?php 	foreach($permissions as $option => $label): ?>
		<label><input type="checkbox" name="permissions[]" value="<?= $option ?>" <?php $form->checked('permissions', $option) ?>> <?= $label ?></label>
<?php 	endforeach ?>
<?php $form->error('permissions') ?>
	</div>
</fieldset>
<?php endwhile ?>
<p><?php $form->add('user', 'Add one more') ?> user.</p>
<div class="field">
	<?php $form->submit('Submit') ?>
</div>
<?php $form->close() ?>