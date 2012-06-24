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
		
		ae::options('form')->set('upload_dir', '/uploads/');
		
		$form['cover'] = $form::file('Book cover')
			->max_size(2, 'The file is too big.') // Megabytes
			->type(array('jpeg', 'png'), 'Only .png and .jpeg files are allowed.'); // Allow only images, but not GIFs
			//->max_width(2000) // pixels
			//->max_height(1000); // pixels
		
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
	
	// public function upload($form, $field)
	// {
	// 	$to = '/uploads/' . $field->file_hash . '.' . $field->file_extension;
	// 	$moved = move_uploaded_file($field->file_path, $to);
	// 	
	// 	if (!$moved)
	// 	{
	// 		$field->error = "File could not be uploaded for an unknown reason. Sorry.";
	// 		return;
	// 	}
	// 	
	// 	// data is serialized and checksumed, so that no one can mess arround with it.
	// 	$data = $field->data();
	// 	
	// 	if (!empty($data))
	// 	{
	// 		return;
	// 	}
	// 	
	// 	$data = array(
	// 		'path' => $to,
	// 		'name' => $field->file_name
	// 	);
	// 	
	// 	// Store data
	// 	$field->data($data);
	// }
	
	public function submit($form)
	{
		if ($form->id() === 'new-book')
		{
			$values = $form->values();
			
			// do something useful
			
			echo $values['cover']['path'];
			echo $values['cover']['size'];
			echo $values['cover']['extension'];
		}
		
		$form['cover']->remove();
	}
}

?>
<?= $form->open() ?>
<?php foreach ($form->fields() as $field): ?>
<?= $field->field('<div class="field {type}">', '</div>') ?>
<?php endforeach ?>
<div class="field button">
	<?= $form->submit('Add book') ?>
</div>
<?= $form->close() ?>