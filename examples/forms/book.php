<?php

$controller = new BookFormController();
$form = ae::form('new-book', $controller);

class BookFormController implements aeFormControllerInterface 
{
	public function setup($form)
	{
		if ($form->id() !== 'new-book')
		{
			return;
		}
		
		$form['title'] = $form::text('Book title', array(
				'class' => 'title'
			))
			->satisfy('is_not', 'foo', 'lorem')
			->required('Please specify the title.')
			->max_length(255, 'The title is too long.');
		
		$form['description'] = $form::textarea('Book description', array(
				'cols' => 60,
				'rows' => 20
			))
			->required('Please specify the description.')
			->min_length(2, 'The description is too short.')
			->max_length(5000, 'The description is too long.');
		
		// Set default values
		$form->values(array('title' => '', 'description' => ''));
	}
	
	public function validate($form, $field, $rule = null, $attributes = null)
	{
		switch ($rule)
		{
			case 'is_not':
				if (is_array($attributes) && in_array($field->value, $attributes))
				{
					$field->error = 'This cannot be a title of the book?';
				} 
				break;
			
			default:
				if ($field->name === 'title' && ucfirst($field->value) === 'Title')
				{
					$field->error = 'Are you sure that is the title of the book?';
				}
				break;
		}
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
<div class="field">
	<?= $field ?>
<?php endforeach ?>
<div class="field button">
	<?= $form->submit('Add book') ?>
</div>
<?= $form->close() ?>