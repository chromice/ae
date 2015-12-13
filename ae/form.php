<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2015 Anton Muraviev <anton@goodmoaning.me>
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# 

ae::options('ae.form', array(
	'frontend_validation' => true // whether to allow HTML5 validation in browsers that support it.
));

ae::import('ae/request.php');

ae::invoke('aeForm');

class aeForm implements ArrayAccess
/*
	Form rendering and validation library.
	
	Here is an example of form and input initialisation and validation:
	
		$form = ae::form('example-form');
		
		$input = $form->single('example-input')
			->required('This field is required!');
		
		if ($form->is_submitted())
		{
			$is_valid = $form->validate();
			
			if ($is_valid)
			{
				$values = $form->values();
				
				echo '<h1>' . $values['example-input'] . '</h1>'
			}
		}
	
	And here is how you can render that form's HTML:
	
		<?= $form->open() ?>
		<div class="field">
			<label for="<?= $input->id() ?>">Enter some text:</label>
			<?= $input->input('text') ?>
			<?= $input->error() ?>
		</div>
		<div class="field">
			<button type="submit">Submit</button>
		</div>
		<?= $form->close() ?>
*/
{
	protected $id;
	protected $fields = array();
	protected $values = array();
	protected $errors = array();
	protected $has_files = false;
	
	public function __construct($form_id)
	{
		if (empty($form_id))
		{
			trigger_error('Form ID cannot be empty.', E_USER_ERROR);
		}
		
		$nonces = ae::session('nonces');
		
		$this->id = $form_id;
		$this->nonce = $nonces[$form_id];
		
		if ($this->is_submitted())
		{
			$this->values($_POST);
		}
	}

	public function single($name)
	/*
		Add a field for scalar value.
	*/
	{
		return $this->fields[$name] = new aeFormField($this->id, $name, false, $this->values[$name], $this->errors[$name]);
	}

	public function multiple($name)
	/*
		Add a field for an array of values (arbitrary length).
	*/
	{
		return $this->fields[$name] = new aeFormField($this->id, $name, true, $this->values[$name], $this->errors[$name]);
	}

	public function sequence($name, $min = 1, $max = null)
	/*
		Add a field for an array of values (controlled length).
	*/
	{
		return $this->fields[$name] = new aeFormFieldSequence($this->id, $name, $this->values[$name], $this->errors[$name], $min, $max);
	}
	
	public function file($name)
	{
		return $this->_file($name, false);
	}
	
	public function files($name)
	{
		return $this->_file($name, true);
	}
	
	protected function _file($name, $multiple)
	{
		$this->has_files = true;
		$this->fields[$name] = new aeFormFileField($this->id, $name, $multiple, $this->values[$name], $this->errors[$name]);
		
		return $this->fields[$name];
	}
	
	public function is_submitted()
	/*
		Returns true if the form with such id is posted.
	*/
	{
		return isset($_POST['__ae_form_id__']) && $_POST['__ae_form_id__'] === $this->id;
	}

	public function validate()
	/*
		Validates all declared fields and returns TRUE on success or FALSE 
		on failure.
	*/
	{
		$result = isset($_POST['__ae_form_nonce__']) && $_POST['__ae_form_nonce__'] === $this->nonce;
		
		foreach ($this->fields as $field)
		{
			$result &= $field->validate();
		}
		
		return $result;
	}

	public function errors()
	/*
		Returns an array of errors for all declared fields.
	*/
	{
		return $this->errors;
	}
	
	public function value($name, $index = null)
	/*
		Returns literal value of the field.
	*/
	{
		if (is_null($index))
		{
			return isset($this->values[$name]) ? $this->values[$name] : null;
		}
		elseif (isset($this->values[$name][$index]))
		{
			return $this->values[$name][$index];
		}
	}
	
	public function values($values = null)
	/*
		Returns literal values of all declared fields (if $values is NULL);
		Or sets new values (if $values is an array).
	*/
	{
		if (is_null($values))
		{
			return array_intersect_key($this->values, $this->fields);
		}
		
		if (is_array($values)) foreach ($values as $field => $value)
		{
			$this->values[$field] = $value;
		}
	}

	public function open($attributes = array())
	/*
		Returns opening form tag and some hidden inputs.
	*/
	{
		$attributes = array_merge(array(
			'action' => aeRequest::url(),
			'novalidate' => !ae::options('ae.form')->get('frontend_validation')
		), $attributes);
		
		$attributes['id'] = $this->id . '-form';
		$attributes['method'] = 'post';
		
		if ($this->has_files)
		{
			$attributes['enctype'] = 'multipart/form-data';
		}
		
		// Update nonce
		$nonces = ae::session('nonces');
		$nonces[$this->id] = md5(uniqid(mt_rand(), true));
		
		return '<form ' . self::attributes($attributes) . '>'
			. '<input type="hidden" name="__ae_form_id__" value="' . $this->id . '" />'
			. '<input type="hidden" name="__ae_form_nonce__" value="' . $nonces[$this->id] . '" />'
			. '<input type="submit" tabindex="-1" style="position:absolute; left:-999em; width:0; overflow:hidden">';
	}

	public function close()
	/*
		Returns a closing form tag.
	*/
	{
		return '</form>';
	}
	
	public static function attributes($attributes)
	/*
		Serializes an array of attributes into a string.
	*/
	{
		$output = array();
		
		foreach ($attributes as $name => $value)
		{
			if ($value === false || strlen($value) === 0)
			{
				continue;
			}
			
			if ($name === $value && !in_array($name, array('id', 'name', 'value'))
			|| $value === true)
			{
				$output[] = ae::escape($name, ae::name);
			}
			else
			{
				$output[] = ae::escape($name, ae::name) . '="' . ae::escape($value, ae::value) . '"';
			}
		}
		
		return implode(' ', $output);
	}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($name)
	{
		return isset($this->fields[$name]);
	}
	
	public function offsetGet($name)
	{
		if (!isset($this->fields[$name]))
		{
			trigger_error('Unknown field "' . $name . '".', E_USER_ERROR);
			
			return;
		}
		
		return $this->fields[$name];
	}
	
	public function offsetSet($name, $field)
	{
		trigger_error('Form fields cannot be set directly. Please use an appropriate factory method.', E_USER_ERROR);
	}
	
	public function offsetUnset($name)
	{
		unset($this->fields[$name], $this->values[$name], $this->errors[$name]);
	}
}

class aeValidator
/*
	Provides validation functionality to the field classes.
	
	Each public method accepts error message as the first parameter.
*/
{
	const month = '\d{4,}-(?:0[1-9]|1[0-2])';
	const week = '\d{4,}-W\d{2}';
	const date = '\d{4,}-\d{2}-\d{2}';
	const datetime = '\d{4,}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[\+\-]\d{2}:\d{2})?';
	const time = '(?:0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9](?:\.\d+)?';
	const integer = '[\+\-]?\d+';
	const decimal = '[\+\-]?(?:\d+(?:\.\d+)?|\.\d+)';
	const numeric = '\d+';
	const alpha = '[a-zA-Z]+';
	const alphanumeric = '[0-9a-zA-Z]+';
	const color = '#[0-9a-fA-F]{6}';
	
	// Complex types
	const email = '([0-9a-zA-Z]([-\+\.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})';
	const url = '((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)';
	const postcode_uk = '(([a-pr-uw-zA-PR-UW-Z]{1}[a-ik-yA-IK-Y]?)([0-9]?[a-hjks-uwA-HJKS-UW]?[abehmnprvwxyABEHMNPRVWXY]?|[0-9]?[0-9]?))\s*([0-9]{1}[abd-hjlnp-uw-zABD-HJLNP-UW-Z]{2})';
	
	protected $required;
	protected $validators = array();
	protected $html = array();
	protected $time_format;
	
	protected function _validate($value, $index = null)
	{
		if (is_array($value))
		{
			$nonempty = array_filter($value);
			
			if (!empty($this->required) && count($nonempty) === 0)
			{
				return $this->required;
			}
			
			foreach ($nonempty as $_key => $_value)
			{
				foreach ($this->validators as $func)
				{
					if ($error = $func($_value, $_key))
					{
						return $error;
					}
				}
			}
		}
		else
		{
			if (!empty($this->required) && strlen($value) === 0)
			{
				return $this->required;
			}
			
			if (strlen($value) > 0) foreach ($this->validators as $func)
			{
				if ($error = $func($value, $index))
				{
					return $error;
				}
			}
		}
	}
	
	public function required($message)
	/*
		Prevents user from submitting an empty string.
	*/
	{
		$this->required = $message;
		$this->html['required'] = true;
		
		return $this;
	}
	
	public function valid_value($message, $misc)
	/*
		Prevents user from submitting a value that does not meet a constraint:
		
		- if `$misc` has a scalar value, value is not equal to it;
		- if `$misc` is an array, value is not in it;
		- if `$misc` is a function, and `$misc($value, $index)` returns FALSE.
	*/
	{
		$this->validators['valid_value'] = function ($value, $index = null) use ($message, $misc) {
			if (is_scalar($misc))
			{
				return $value != $misc ? $message : null;
			}
			elseif (is_array($misc))
			{
				return !in_array($value, $misc) ? $message : null;
			}
			elseif (is_callable($misc))
			{
				return call_user_func($misc, $value, $index) === false ? $message : null;
			}
		};
		
		return $this;
	}
	
	public function valid_pattern($message, $pattern)
	/*
		Prevents user from submitting a value that does not patch the pattern.
		
		NB! The pattern must be in HTML5 format, e.g. `[a-z0-9]`, i.e. no 
		slashes or flags.
		
		In addition to validating date/time formats (month, week, date, 
		datetime, time) the library also validates the value and switches 
		min_value(), max_value() constraints to time format:
		
			$field->valid_pattern('Invalid date format', aeValidator::date)
				->min_value('Must be from now on and till the end of times', 
				date('Y-m-d'));
	*/
	{
		if (in_array($pattern, array(
			aeValidator::month,
			aeValidator::week,
			aeValidator::date,
			aeValidator::datetime,
			aeValidator::time
		)))
		{
			$time = true;
			$this->time_format = '/(?:' . $pattern . ')$/';
		}
		else
		{
			$time = false;
		}
		
		$this->html['pattern'] = '\s*' . $pattern . '\s*';
		$this->validators['valid_pattern'] = function ($value) use ($message, $pattern, $time) {
			return preg_match('/^(?:' . $pattern . ')$/', $value) !== 1 
				|| $time && strtotime($value) === FALSE ? $message : null;
		};
		
		return $this;
	}
	
	public function min_length($message, $length)
	/*
		Prevents user from submitting a value shorter then `$length`.
	*/
	{
		$this->validators['min_length'] = function ($value) use ($message, $length) {
			return strlen($value) < $length ? $message : null;
		};

		return $this;
	}
	
	public function max_length($message, $length)
	/*
		Prevents user from submitting a value longer then `$length`.
	*/
	{
		$this->html['maxlength'] = $length;
		$this->validators['max_length'] = function ($value) use ($message, $length) {
			return strlen($value) > $length ? $message : null;
		};

		return $this;
	}
	
	public function min_value($message, $limit)
	/*
		Prevents user from submitting a number greater then `$limit` or date 
		after a certain time.
	*/
	{
		$this->html['min'] = $limit;
		
		if (!empty($this->time_format))
		{
			$format = $this->time_format;
			$this->validators['min_value'] = function ($value) use ($message, $limit, $format) {
				return preg_match($format, $value) !== 1 || preg_match($format, $limit) !== 1
					|| strtotime($value) < strtotime($limit) ? $message : null;
			};
		}
		else
		{
			$this->validators['min_value'] = function ($value) use ($message, $limit) {
				return $value < $limit ? $message : null;
			};
		}

		return $this;
	}
	
	public function max_value($message, $limit)
	/*
		Prevents user from submitting a number less then `$limit` or date past 
		a certain time.
	*/
	{
		$this->html['max'] = $limit;
		
		if (!empty($this->time_format))
		{
			$format = $this->time_format;
			$this->validators['max_value'] = function ($value) use ($message, $limit, $format) {
				return preg_match($format, $value) !== 1 || preg_match($format, $limit) !== 1
					|| strtotime($value) > strtotime($limit) ? $message : null;
			};
		}
		else
		{
			$this->validators['max_value'] = function ($value) use ($message, $limit) {
				return $value > $limit ? $message : null;
			};
		}

		return $this;
	}
}

class aeFormField extends aeValidator
{
	protected $form_id;
	
	protected $name;
	protected $index;
	protected $value;
	protected $error;
	
	public function __construct($form_id, $name, $index, &$value, &$error, &$html = null)
	{
		$this->form_id = $form_id;
		$this->name = $name;
		
		$this->index = $index;
		$this->value =& $value;
		$this->error =& $error;
		
		if (!is_null($html))
		{
			$this->html =& $html;
		}
		
		if ($index === true)
		{
			$this->value = is_array($this->value) ? array_map(function ($value) {
				return is_scalar($value) ? trim($value) : '';
			}, $this->value) : array();
		}
		else
		{
			$this->value = is_scalar($this->value) ? trim($this->value) : '';
		}
	}
	
	public function index()
	/*
		Returns current explicit index, if the field has one.
	*/
	{
		if (is_numeric($this->index))
		{
			return $this->index;
		}
	}
	
	public function id()
	/*
		Returns value of the element's id attribute, or empty string for field 
		with multple values.
	*/
	{
		if ($this->index !== true)
		{
			return $this->form_id . '-' . preg_replace('/[\s_]+/', '-', $this->name) 
				. ($this->index !== false ? '-' . $this->index : '');
		}
	}
	
	public function name()
	/*
		Returns value of the element's name attribute.
	*/
	{
		if ($this->index === false) 
		{
			return $this->name;
		}
		else
		{
			return $this->name . (($this->index !== true) ? '[' . $this->index . ']' : '[]');
		}
	}
	
	public function value($index = null)
	/*
		Returns value of the element's value attribute. 
	*/
	{
		if ($this->index !== true || is_null($index))
		{
			return $this->value;
		}
		else
		{
			return isset($this->value[$index]) ? $this->value[$index] : null;
		}
	}
	
	public function input($type, $value = null, $attributes = array())
	/*
		Renders a <input> element for the field.
	*/
	{
		if (is_array($value))
		{
			$attributes = $value;
			$value = null;
		}
		
		if (in_array($type, array('checkbox', 'radio')))
		{
			if (empty($attributes['id']))
			{
				$attributes['id'] = false;
			}
			
			$attributes['class'] = $this->id();
			$attributes['checked'] = $this->_matches(!is_null($value) ? $value : '1') ? 'checked' : '';
		}
		else 
		{
			$attributes = array_merge($this->html, $attributes);
		}
		
		if (!in_array($type, array('text', 'search', 'url', 'tel', 'email', 'password')))
		{
			unset($attributes['pattern']);
		}
		
		if (!in_array($type, array('number', 'range', 'date', 'datetime', 'datetime-local', 'month', 'time', 'week')))
		{
			unset($attributes['min']);
			unset($attributes['max']);
		}
		
		$attributes['type'] = $type;
		$attributes['value'] = !is_null($value) ? $value : $this->value;
		
		return '<input ' . $this->_attributes($attributes) . '>';
	}
	
	public function textarea($attributes = array())
	/*
		Renders a <textarea> element for the field.
	*/
	{
		$attributes['required'] = isset($attributes['required']) 
			? !empty($attributes['required'])
			: !empty($this->html['required']);
		
		if (!empty($this->html['maxlength']))
		{
			$attributes['maxlength'] = $this->html['maxlength'];
		}
		
		return '<textarea ' . $this->_attributes($attributes) . '>' 
			. ae::escape($this->value, ae::value) 
			. '</textarea>';
	}
	
	public function select($options, $attributes = array())
	/*
		Renders a <select> element for the field.
	*/
	{
		$attributes['required'] = isset($attributes['required']) 
			? !empty($attributes['required'])
			: !empty($this->html['required']);
		$attributes['multiple'] = $this->index === true;
		
		return '<select ' . $this->_attributes($attributes) . '>' 
			. $this->_options($options)
			. '</select>';
	}
	
	protected function _options($options, $indent = 0)
	{
		$output = array();
		
		foreach ($options as $key => $value) 
		{
			if (is_scalar($value))
			{
				$output[] = '<option value="' . ae::escape($key, ae::value) . '"' 
					. ($this->_matches($key) ? ' selected' : '') . '>' . $value . '</option>';
			}
			elseif (is_array($value))
			{
				$output[] = '<optgroup label="' . ae::escape($key, ae::value) . '">' 
					. $this->_options($value, $indent + 1)
					. '</optgroup>';
			}
		}
		
		return str_repeat("\t", $indent) . implode("\n" . str_repeat("\t", $indent + 1), $output) . "\n";
	}
	
	protected function _attributes($attributes)
	{
		if (!isset($attributes['name']) 
		|| $attributes['name'] !== false)
		{
			$attributes['name'] = $this->name();
		}
		
		if (!isset($attributes['id']))
		{
			$attributes['id'] = $this->id();
		}
		
		return aeForm::attributes($attributes);
	}
	
	protected function _matches($value)
	{
		$value = (string) $value;
		
		return $this->index === true
			? in_array($value, $this->value)
			: $this->value === $value;
	}
	
	public function validate()
	/*
		Validates the field and returns validation status as TRUE or FALSE.
	*/
	{
		$this->error = $this->_validate($this->value, $this->index);
		
		return empty($this->error);
	}
	
	public function has_error()
	/*
		Returns TRUE, if field has an error, or FALSE otherwise.
	*/
	{
		return !empty($this->error);
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	/*
		If field has an error, returns it wrapped in customisable HTML.
	*/
	{
		return !empty($this->error) ? $before . $this->error . $after : '';
	}
}

class aeFormFieldSequence extends aeValidator implements ArrayAccess, Iterator, Countable
{
	protected $form_id;
	protected $name;

	protected $min;
	protected $max;
	
	protected $values;
	protected $errors;
	protected $fields = array();
	
	protected $required_callback;
	
	public function __construct($form_id, $name, &$values, &$errors, $min = 1, $max = null)
	{
		$this->form_id = $form_id;
		$this->name = $name;
		
		$this->min = $min;
		$this->max = $max;
		
		$this->values =& $values;
		$this->values = is_array($this->values) ? $this->values : array();
		$this->errors =& $errors;
		
		
		if (!is_null($this->max))
		{
			$this->values = array_slice($this->values, 0, $this->max, true);
		}
		
		$append = $this->min - count($this->values);
		
		for ($i=0; $i < $append; $i++) 
		{ 
			$this->values[] = '';
		}

		foreach ($this->values as $i => $value)
		{
			$this->fields[$i] = new aeFormField($this->form_id, $this->name, $i, $this->values[$i], $this->errors[$i], $this->html);
		}
	}

	public function count()
	/*
		Returns the number of fields in the sequence.
	*/
	{
		return count($this->fields);
	}
	
	public function required($message, $callback = null)
	/*
		Overloads `aeValidator::required()` to allow setting a callback 
		function, which accepts field index as the only argument and must 
		return TRUE, if the field is required.
	*/
	{
		parent::required($message);
		
		$this->required_callback = $callback;
		
		if (!is_null($this->required_callback))
		{
			unset($this->html['required']);
		}
		
		return $this;
	}
	
	public function validate()
	/*
		Validates all fields in the sequence and returns TRUE or FALSE.
	*/
	{
		$result = true;
		
		foreach ($this->values as $index => $value)
		{
			if (is_callable($this->required_callback) && strlen($value) == 0) 
			{
				$this->errors[$index] = call_user_func($this->required_callback, $index) === true ? $this->required : null;
			}
			else
			{
				$this->errors[$index] = $this->_validate($value, $index);
			}
			
			$result &= empty($this->errors[$index]);
		}
		
		$count = count($this->values);
		
		return $result && $count >= $this->min && (is_null($this->max) || $count <= $this->max);
	}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($offset)
	{
		return isset($this->fields[$offset]);
	}
	
	public function offsetGet($offset)
	{
		return isset($this->fields[$offset]) 
			? $this->fields[$offset] 
			: $this->fields[$offset] = new aeFormField($this->form_id, $this->name, $offset, $this->values[$offset], $this->errors[$offset], $this->html);
	}
	
	public function offsetSet($offset, $value)
	{
		if (isset($this->fields[$offset]) 
		|| !is_null($this->max) && $this->max <= count($this->fields))
		{
			return;
		}
		
		if (is_null($offset)) 
		{
			$offset = empty($this->fields) ? 0 : max(array_keys($this->fields)) + 1;
		}
		
		$this->values[$offset] = $value;
		$this->fields[$offset] = new aeFormField($this->form_id, $this->name, $offset, $this->values[$offset], $this->errors[$offset], $this->html);
	}
	
	public function offsetUnset($offset)
	{
		unset($this->fields[$offset], $this->values[$offset], $this->errors[$offset]);
	}
	
	// ===========================
	// = Iterator implementation =
	// ===========================
	
	public function rewind()
	{
		reset($this->fields);
	}
	
	public function current()
	{
		return current($this->fields);
	}
	
	public function key()
	{
		return key($this->fields);
	}
	
	public function next()
	{
		next($this->fields);
	}
	
	public function valid()
	{
		return !is_null(key($this->fields));
	}
}

class aeFormFileField
{
	protected $form_id;
	
	protected $name;
	protected $multiple;
	protected $value;
	protected $error;
	
	protected $required;
	protected $accept;
	protected $validators = array();
	
	public function __construct($form_id, $name, $multiple, &$value, &$error)
	{
		$this->form_id = $form_id;
		$this->name = $name;
		
		$this->multiple = $multiple;
		$this->value =& $value;
		$this->error =& $error;
		
		$this->error = array();
		
		if ($this->multiple && is_array($this->value))
		{
			foreach ($this->value as $index => $value)
			{
				if (empty($value['name']) || empty($value['path']))
				{
					continue;
				}
				
				$file = ae::file($value['path'], $value['name']);
				
				if ($file->exists())
				{
					$this->value[$index] = $file;
				}
				else
				{
					unset($this->value[$index]);
				}
			}
		}
		elseif (!$this->multiple && !empty($this->value))
		{
			$value = $this->value;
			
			if (empty($value['name']) || empty($value['path']))
			{
				return;
			}
			
			$file = ae::file($value['path'], $value['name']);
			
			if ($file->exists())
			{
				$this->value = $file;
			}
			else
			{
				$this->value = null;
			}
		}
	}
	
	public function upload($path)
	{
		// TODO: Validate if destination exits and is writable.
		$destination = ae::resolve($path, false);
		
		// Check if there any files to handle
		if (empty($_FILES[$this->name]))
		{
			return;
		}
		
		if (is_array($_FILES[$this->name]['error']))
		{
			// Streamline files array
			$files = array();
			
			foreach ($_FILES[$this->name] as $attribute => $values)
			{
				foreach ($values as $index => $value)
				{
					$files[$index][$attribute] = $value; 
				}
			}
			
			foreach ($files as $file)
			{
				$file = $this->_upload($file, $destination);
				
				if (is_a($file, 'aeFile'))
				{
					$this->value[] = $file;
				}
			}
		}
		else
		{
			$file = $this->_upload($_FILES[$this->name], $destination);
			
			if (is_a($file, 'aeFile'))
			{
				$this->value = $file;
			}
		}
		
		return $this;
	}
	
	protected function _upload($file, $destination)
	{
		// Check if all necessary data is there
		if (!isset($file['tmp_name']) || !isset($file['name']) || !isset($file['error']))
		{
			return;
		}
		
		// Check if file is uploaded at all
		switch ($file['error'])
		{
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				if (!empty($this->required))
				{
					$this->error[] = $this->required;
				}
				return;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$this->error[] = 'Exceeded filesize limit.';
				return;
			default:
				$this->error[] = 'File could not be uploaded.';
				return;
		}
		
		$file = ae::file($file['tmp_name'], $file['name']);
		
		// Check if the file is indeed uploaded
		if (!$file->is_uploaded())
		{
			return;
		}
		
		// Check if the file meets all constraints
		$error = $this->_validate($file);
		
		if (!empty($error))
		{
			$this->error[] = $error;
			
			return;
		}
		
		try 
		{
			// Move file to the destination
			$target = $destination . '/' . $file->hash() . '.' . $file->extension();
			
			$file->move($target);
		} 
		catch (aeFileException $e)
		{
			$this->error[] = 'File could not be uploaded.';
			
			return;
		}
		
		return $file;
	}
	
	public function id()
	/*
		Returns value of the element's id attribute, or empty string for field 
		with multple values.
	*/
	{
		return $this->form_id . '-' . preg_replace('/[\s_]+/', '-', $this->name);
	}
	
	public function name()
	/*
		Returns value of the element's name attribute.
	*/
	{
		return $this->name . ($this->multiple ? '[]' : '');
	}
	
	public function value($index = null)
	/*
		Returns uploaded file(s).
	
		See aeFile class.
	*/
	{
		if (!$this->multiple || is_null($index))
		{
			return $this->value;
		}
		else
		{
			return isset($this->value[$index]) ? $this->value[$index] : null;
		}
	}
	
	public function input($attributes = array())
	/*
		Renders a <input> element for file field.
	*/
	{
		$files = array();
		
		if ($this->multiple && is_array($this->value))
		{
			$files = $this->value;
		}
		elseif (is_a($this->value, 'aeFile'))
		{
			$files = array($this->value);
		}
		
		// Render file input
		$attributes['type'] = 'file';
		$attributes['multiple'] = $this->multiple === true;
		
		$output =  '<input ' . $this->_attributes($attributes) . ">\n";
		
		// Add uploaded file data as hidden inputs.
		foreach ($files as $index => $file)
		{
			$output.= '<input ' . aeForm::attributes(array(
				'type' => 'hidden',
				'name' => $this->name() . '[name]',
				'value' => $file->name()
			)) . ">\n";
			
			$output.= '<input ' . aeForm::attributes(array(
				'type' => 'hidden',
				'name' => $this->name() . '[path]',
				'value' => $file->path()
			)) . ">\n";
		}
		
		return $output;
	}
	
	protected function _attributes($attributes)
	{
		if (!isset($attributes['name']) 
		|| $attributes['name'] !== false)
		{
			$attributes['name'] = $this->name();
		}
		
		if (!isset($attributes['id']))
		{
			$attributes['id'] = $this->id();
		}
		
		return aeForm::attributes($attributes);
	}
	
	public function validate()
	/*
		Validates the field and returns validation status as TRUE or FALSE.
	*/
	{
		if (empty($this->value))
		{
			return empty($this->error);
		}
		
		if ($this->multiple === true)
		{
			foreach ($this->value as $file)
			{
				if ($errors = $this->_validate($file))
				{
					$this->error += (array) $errors;
				}
			}
		}
		else
		{
			if ($errors = $this->_validate($this->value))
			{
				$this->error += (array) $errors;
			}
		}
		
		return empty($this->error);
	}
	
	protected function _validate($file)
	{
		if (empty($file))
		{
			return;
		}
		
		$accept = $this->accept;
		
		// Validate type first
		if (is_callable($accept))
		{
			if ($error = $accept($file))
			{
				return $error;
			}
		}
		
		// Validate size and dimensions
		$errors = array();
		
		foreach ($this->validators as $validator)
		{
			if ($error = $validator($file))
			{
				$errors[] = $error;
			}
		}
		
		if (!empty($errors))
		{
			return $errors;
		}
	}
	
	public function has_error()
	/*
		Returns TRUE, if field has an error, or FALSE otherwise.
	*/
	{
		return !empty($this->error);
	}
	
	public function error($before = '<em class="error">', $after = '</em>', $before_item = null, $after_item = null)
	/*
		If present, returns an error message wrapped in customisable HTML.
	*/
	{
		$error = $before_item 
			. implode($after_item . ' ' . $before_item, $this->error) 
			. $after_item;
		
		return !empty($error) ? $before . $error . $after : '';
	}
	
	// ==============
	// = Validators =
	// ==============
	
	public function required($message)
	{
		$this->required = $message;
		
		return $this;
	}
	
	public function accept($message, $types)
	{
		if (!is_array($types))
		{
			$types = explode(',', $types);
		}
		
		$types = array_map(function ($type) {
			return trim($type, '* ');
		}, $types);
		
		$this->accept = function ($file) use ($types, $message) {
			try
			{
				$type = preg_quote($file->extension());
				$mime = preg_quote($file->mime());
			}
			catch (aeFileException $e)
			{
				return $message;
			}
			
			foreach ($types as $_type)
			{
				if ($type{0} === '.' && '.' . $type === $_type)
				{
					return;
				}
				elseif ($type{0} !== '.' && strpos($mime, $_type) !== false)
				{
					return;
				}
			}
			
			return $message;
		};
		
		return $this;
	}
	
	public function min_size($message, $size)
	{
		$this->validators[] = function ($file) use ($size, $message) {
			return $file->size() < $size ? $message : null;
		};
		
		return $this;
	}
	
	public function max_size($message, $size)
	{
		$this->validators[] = function ($file) use ($size, $message) {
			return $file->size() > $size ? $message : null;
		};
		
		return $this;
	}
	
	public function min_width($message, $width)
	{
		$this->validators[] = function ($file) use ($width, $message) {
			return $file->width() < $width ? $message : null;
		};
		
		return $this;
	}
	
	public function max_width($message, $width)
	{
		$this->validators[] = function ($file) use ($width, $message) {
			return $file->width() > $width ? $message : null;
		};
		
		return $this;
	}
	
	public function min_height($message, $height)
	{
		$this->validators[] = function ($file) use ($height, $message) {
			return $file->height() < $height ? $message : null;
		};
		
		return $this;
	}

	public function max_height($message, $height)
	{
		$this->validators[] = function ($file) use ($height, $message) {
			return $file->height() > $height ? $message : null;
		};
		
		return $this;
	}
}
