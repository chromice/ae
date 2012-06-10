<?php

// Create form
$form = ae::form('login-form');

// Create fields
$form['login'] = $form::text('Login')
	->properties(
		'label' => 'Username' // alter label
	)
	->attributes(array(
		'class' => 'login',
		'maxlength' => 20
	));
$form['pass'] = $form::password('Password');
$form['remember'] = $form::checkbox('Remember me');

// If form is lready posted...
if ($form->is_posted())
{
	// ...process it here...
	$values = $form->values();
	
	if ($values['login'] === 'user' && $values['password'] === 'password')
	{
		// login user
	}
	else
	{
		$form['user']->error = 'Wrong user name or password.';
	}
}

?>
<?= $form->open() ?>
<div class="field">
	<?= $form['user'] ?>
	<?php // echo $form['user']->label() ?>
	<?php // echo $form['user']->field() ?>
	<?php // echo $form['user']->error('<em class="error">', '</em>') ?>
</div>
<?php custom_render_function($form['pass'], $form['remember']); ?>
<div class="field button">
	<?php // echo $form->submit('Login') ?>
	<?= $form::button('submit', 'login-form', 'Login') ?>
</div>
<?= $form->close() ?>

<?php

function custom_render_function($field, $checbox)
{
?>
<div class="field with-checkbox">
	<?php $field->label() ?>
	<?php $field->field() ?> <?php $field->error() ?>
	<?php $checkbox->field() ?> <?php $checkbox->error() ?>
</div>
<?php
}
?>