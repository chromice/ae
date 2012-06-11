<?php

$controller = new BookFormController();
$form = ae::form('new-book', $controller);

class BookFormController implements aeFormControllerInterface 
{
	public function setup(&$form)
	{
		if ($form->id() !== 'new-book')
		{
			return;
		}
		
		$form['title'] = $form::text('Book title', array(
				'class' => 'title'
			))
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
	
	public function validate(&$form)
	{
		if (ucfirst($form['title']->value) === 'Title')
		{
			$form['title']->error = 'Are you sure that is the title?';
		}
	}
	
	public function submit(&$form)
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