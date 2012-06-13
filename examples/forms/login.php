<?php

// Create form
$form = ae::form('login-form');

// Create fields
$form['login'] = $form::text('Login', array(
	'class' => 'login',
	'maxlength' => 20
));
$form['login']->label = 'User name';
$form['pass'] = $form::password('Password');
$form['remember'] = $form::checkbox('remember me');

// if form is already posted...
if ($form->submitted())
{
	// ...process it here...
	if ('user' === $form['login']->value && 'password' === $form['password']->value)
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
	<?php // echo $form['user']->input() ?>
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
	<?= $field->label() ?>
	<?= $field->input() ?> <?= $field->error() ?>
	<?= $checkbox->input() ?> <?= $checkbox->error() ?>
</div>
<?php
}
?>