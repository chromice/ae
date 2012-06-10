<?php

$form = ae::form('new-book');
$controller = new BookFormController();

$form->delegate($controller);

class BookFormController implements aeFormController 
{
	public function setup(&$form)
	{
		$form['title'] = $form::text('Book title')
			->attributes(array(
				'class' => 'title'
			));
		$form['description'] = $form::textarea('Book description', 60, 20); // Label, Cols, Rows
		
		$form->default(array('title' => '', 'description' => '')); // Just an example
	}
	
	public function serialize($values)
	{
		return $values;
	}
	
	public function unserialize($values)
	{
		return $values;
	}
	
	public function validate($form)
	{
		$values = $form->values();
		$errors = array();
		
		if (empty($values['title']))
		{
			$errors['title'] = 'Please specify the title.';
		}
		
		if (empty($values['description']))
		{
			$errors['description'] = 'Please specify the description.';
		}
		
		return $errors;
	}
	
	public function submit($form)
	{
		if ($form->id() === 'new-book')
		{
			$values = $form->values();
			
			// do something useful
		}
	}
}

?>
<?= $form->open() ?>
<?php foreach ($form->fields() as $field): ?>
<div class="field <?= $field->attribute('class') ?>">
	<?= $field ?>
<?php endforeach ?>
<div class="field button">
	<?= $form->submit('Add book') ?>
</div>
<?= $form->close() ?>