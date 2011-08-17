<?php

$controller = ae::load('my_form_controller.php');
$form = ae::load('form.php', $controller);
// or 
// $form = ae::load('form.php', array($controller1, $controller2, ...));
// 

$form->open();
?>
<div class="field">
	<label for="field_name">Field name</label>
	<?php $form['field_name'] ?>
	<!-- Outputs a complete form control (and an error message) for "field_name", e.g. -->
	<!-- <input type="text" name="field_name" value="some text" id="field_name"> -->
	<!-- <em class="error">Some error</em> -->
</div>
<!-- or -->
<div class="field">
	<label for="field_name">Field name</label>
	<?php $form['field_name']->control() ?>
	<?php $form['field_name']->error('<label class="error" for="field_name">','</label>') ?>
</div>
<!-- Matrices -->
<table>
<?php for ($i=0; $i < 4; $i++): ?>
	<tr>
<?php 	for ($j=0; $j < 4; $j++): ?>
		<td><?php $form['matrix'][$i][$j]->control() ?></td>
<?php 	endfor ?>
	</tr>
<?php endfor ?>
</table>
<?php $form['matrix']->errors('<li>','</li>','<ul class="erros">', '</ul>') ?>
<!-- Groups -->
<div class="field">
	<label for="foo">Foo label</label>
	<?php $form['group']['field'] ?> <!-- Invalid field names are interpretted as group identifiers --> 
</div>
<!-- Groups can be repeatable, just as fields: -->
<?php for ($i=0; $i < $form['repeatable']->length() + ($add_another ? 1 : 0); $i++): ?>
<div class="field">
	<label for="repeatable_<?= $i ?>_foo">Foo label</label>
	<?php $form['repeatable'][$i]['foo'] ?>
</div>
<div class="field">
	<label for="repeatable_<?= $i ?>_bar">Bar label</label>
	<?php $form['repeatable'][$i]['bar'] ?>
</div>
<?php endfor ?>
<?php $form->close() ?>