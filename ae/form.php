<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2013 Anton Muraviev <chromice@gmail.com>
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

ae::invoke('aeForm');

class aeForm
/*
	Form submission and validation handling library.
*/
{
	// Numbers
	const valid_number = 1;
	const valid_integer = 3;
	const valid_octal = 5;
	const valid_hexadecimal = 9;
	const valid_decimal = 17;
	const valid_float = 33;
	
	// Email
	const valid_email = 64;
	
	// URL
	const valid_url = 128;
	const valid_url_path = 384;
	const valid_url_query = 640;
	
	// IP
	const valid_ip = 1024;
	const valid_ipv4 = 3072;
	const valid_ipv6 = 5120;
	const valid_public_ip = 9216;
	
	protected $id;
	protected $fields = array();
	protected $values = array();
	protected $errors = array();
	
	public function __construct($form_id)
	{
		if (empty($form_id))
		{
			trigger_error('Form ID cannot be empty.', E_USER_ERROR);
		}
		
		$this->id = $form_id;
		
		if ($this->is_posted())
		{
			$this->values($_POST);
		}
	}

	public function single($name)
	/*
		Add a field for scalar value.
	*/
	{
		return $this->fields[$name] = new aeFormField($name, null, $this->values[$name], $this->errors[$name]);
	}

	public function multiple($name)
	/*
		Add a field for an array of values (arbitrary length).
	*/
	{
		return $this->fields[$name] = new aeFormFieldMultiple($name, $this->values[$name], $this->errors[$name]);
	}

	public function sequence($name, $min = 1, $max = null)
	/*
		Add a field for an array of values (controlled length).
	*/
	{
		return $this->fields[$name] = new aeFormFieldSequence($name, $this->values[$name], $this->errors[$name], $min, $max);
	}

	public function is_posted()
	/*
		Returns true if the form with such id is posted.
	*/
	{
		return isset($_POST['__ae_form_id__']) 
			&& $_POST['__ae_form_id__'] === $this->id;
	}

	public function validate()
	/*
		Validates all declared fields and returns TRUE on success or FALSE on failure.
	*/
	{
		$result = true;
		
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

	public function open($action = null)
	/*
		Returns opening form tag and some hidden inputs.
	*/
	{
		if (empty($action))
		{
			$action = ae::request()->uri();
		}
		
		return '<form id="' . $this->id . '" action="' . $action . '" method="post">'
			. '<input type="hidden" name="__ae_form_id__" value="' . $this->id . '" />'
			. '<input type="submit" tabindex="-1" style="position:absolute;left:-9999px;">';
	}

	public function close()
	/*
		Returns a closing form tag.
	*/
	{
		return '</form>';
	}

	public function value($name, $value = null)
	/*
		Returns literal value of the field (if $value is NULL);
		or sets it to new value (if value is not NULL).
	*/
	{
		if (is_null($value))
		{
			return isset($this->values[$name]) ? $this->values[$name] : null;
		}
		
		$this->values[$name] = $value;
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
		
		if (is_array($values))
		{
			$this->values = array_merge($this->values, $values);
		}
	}
}

class aeFormValidator
/*
	Provides validation functionality to the inheriting field classes.
	
	Each public method accepts error message as the first parameter.
*/
{
	protected $required;
	protected $validators = array();
	
	protected function _validate($value)
	{
		if (!empty($this->required) && strlen($value) === 0)
		{
			return $this->required;
		}
		
		foreach ($this->validators as $func)
		{
			if ($error = $func($value))
			{
				return $error;
			}
		}
	}
	
	public function required($message)
	/*
		Prevents user from submitting an empty value.
	*/
	{
		$this->required = $message;
		
		return $this;
	}
	
	public function format($message, $format)
	/*
		Prevents user from submitting a value of wrong format.
		
		The second parameter must be either:
		
		- a regular expression (string);
		- one of the aeForm::valid_* flags (int).
	*/
	{
		$filter = FILTER_DEFAULT;
		$options = null;
		
		if (is_string($format))
		{
			$filter = FILTER_VALIDATE_REGEXP;
			$options = array('options' => array('regexp' => $format));
		}
		else if ($format & aeForm::valid_number)
		{
			$format ^= aeForm::valid_number;
			
			switch ($format)
			{
				case aeForm::valid_number:
					$this->validators[] = function($value) use ($message) {
						return !is_numeric($value) ? $message : null;
					};
					return $this;
				case aeForm::valid_integer:
					$filter = FILTER_VALIDATE_INT;
					break;
				case aeForm::valid_octal:
					$filter = FILTER_VALIDATE_INT;
					$options = FILTER_FLAG_ALLOW_OCTAL;
					break;
				case aeForm::valid_hexadecimal:
					$filter = FILTER_VALIDATE_INT;
					$options = FILTER_FLAG_ALLOW_HEX;
					break;
				case aeForm::valid_decimal:
					$filter = FILTER_VALIDATE_FLOAT;
					$options = array('options' => array('decimal' => '.'));
					break;
				case aeForm::valid_float:
					$filter = FILTER_VALIDATE_FLOAT;
					break;
			}
		}
		else if ($format === aeForm::valid_email)
		{
			$filter = FILTER_VALIDATE_EMAIL;
		}
		else if ($format & aeForm::valid_url)
		{
			$format ^= aeForm::valid_url;
			$filter = FILTER_VALIDATE_URL;
			$options = ($format & aeForm::valid_url_query ? FILTER_FLAG_QUERY_REQUIRED : 0)
				| ($format & aeForm::valid_url_path ? FILTER_FLAG_PATH_REQUIRED : 0);
		}
		else if ($format & aeForm::valid_ip)
		{
			$format ^= aeForm::valid_ip;
			$filter = FILTER_VALIDATE_IP;
			$options = ($format & aeForm::valid_ipv4 ? FILTER_FLAG_IPV4 : 0)
				| ($format & aeForm::valid_ipv6 ? FILTER_FLAG_IPV6 : 0)
				| ($format & aeForm::valid_public_ip ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : 0);
		}
		
		$this->validators[] = function($value) use ($message, $filter, $options) {
			return filter_var($value, $filter, $options) === false ? $message : null;
		};
		
		return $this;
	}
	
	public function min_length($message, $length)
	/*
		Prevents user from submitting a value shorter then $length.
	*/
	{
		$this->validators[] = function($value) use ($message, $length) {
			return strlen($value) < $length ? $message : null;
		};

		return $this;
	}
	
	public function max_length($message, $length)
	/*
		Prevents user from submitting a value longer then $length.
	*/
	{
		$this->validators[] = function($value) use ($message, $length) {
			return strlen($value) > $length ? $message : null;
		};

		return $this;
	}
	
	public function min_value($message, $limit)
	/*
		Prevents user from submitting a number greater then $limit.
	*/
	{
		$this->validators[] = function($value) use ($message, $limit) {
			return $value < $limit ? $message : null;
		};

		return $this;
	}
	
	public function max_value($message, $limit)
	/*
		Prevents user from submitting a number less then $limit.
	*/
	{
		$this->validators[] = function($value) use ($message, $limit) {
			return $value > $limit ? $message : null;
		};

		return $this;
	}

	public function options($message, $options)
	/*
		Prevents user from submitting a value that is not in $options array.
	*/
	{
		if (array_values($options) !== $options)
		{
			$options = array_keys($options);
		}
		
		$this->validators[] = function($value) use ($message, $options) {
			return !in_array($value, $options) ? $message : null;
		};
		
		return $this;
	}
}

class aeFormField extends aeFormValidator
/*
	Represents a scalar field, e.g.:
	
		<input name="field" value="foo" />
*/
{
	protected $name;
	protected $index;
	protected $value;
	protected $error;
	
	public function __construct($name, $index, &$value, &$error)
	{
		$this->name = ae::escape($name, ae::identifier);
		$this->index = ae::escape($index, ae::identifier);
		$this->value =& $value;
		$this->error =& $error;
		
		$this->value = is_array($this->value) ? array_map('trim', $this->value) : trim($this->value);
	}
	
	public function __get($name)
	/*
		Returns name, index, value or error parameter as an HTML-safe string.
	*/
	{
		switch ($name) 
		{
			case 'name':
			case 'index':
				return $this->$name;
			case 'error':
			case 'value':
				return is_array($this->$name) 
					? array_map(array('ae','escape'), $this->$name, ae::attribute)
					: ae::escape($this->$name, ae::attribute);
			default:
				trigger_error('Uknown property: ' . $name, E_USER_ERROR);
		}
	}
	
	public function __set($name, $value)
	/*
		Sets name, index, value or error parameter.
	*/
	{
		switch ($name) 
		{
			case 'name':
			case 'index':
				$this->$name = ae::escape($value, ae::identifier);
				return;
			case 'error':
			case 'value':
				$this->$name = $value;
				return;
			default:
				trigger_error('Uknown property: ' . $name, E_USER_ERROR);
		}
	}
	
	public function __isset($name)
	{
		switch ($name) 
		{
			case 'name':
			case 'index':
			case 'error':
			case 'value':
				return !empty($this->$name);
			default:
				trigger_error('Uknown property: ' . $name, E_USER_ERROR);
		}
	}

	public function checked($value)
	/*
		Returns 'checked' if $value matches. Useful for radio and checkbox inputs.
	*/
	{
		return $this->_matches($value) ? 'checked' : '';
	}

	public function selected($value)
	/*
		Returns 'selected' if $value matches. Useful for select/option inputs.
	*/
	{
		return $this->_matches($value) ? 'selected' : '';
	}
	
	protected function _matches($value)
	{
		return is_scalar($this->value) && (string) $this->value === (string) $value;
	}
	
	public function validate()
	/*
		Validates the field and returns TRUE or FALSE.
	*/
	{
		$this->error = $this->_validate($this->value);
		
		return empty($this->error);
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	/*
		Returns field error wrapped in customisable HTML, if field has an error.
	*/
	{
		return !empty($this->error) ? $before . $this->error . $after : '';
	}
}

class aeFormFieldMultiple extends aeFormField
/*
	Represent an array of fields, e.g.:
	
		<input name="field[]" type="checkbox" value="foo" /> Foo
		<input name="field[]" type="checkbox" value="bar" /> Bar
		<input name="field[]" type="checkbox" value="zop" /> Zop
*/
{
	public function __construct($name, &$value, &$error)
	{
		parent::__construct($name, null, $value, $error);
		
		$this->value = !is_array($this->value) ? array() : array_values($this->value);
	}
	
	protected function _validate($values)
	{
		if (!empty($this->required) && empty($values))
		{
			return $this->required;
		}
		
		foreach ($values as $value)
		{
			foreach ($this->validators as $func)
			{
				if ($error = $func($value))
				{
					return $error;
				}
			}
		}
	}
	
	protected function _matches($value)
	{
		return is_array($this->value) && in_array((string) $value, $this->value);
	}
}

class aeFormFieldSequence extends aeFormValidator implements ArrayAccess, Iterator
/*
	Represent a list of fields of predefined length, e.g.:

		<input name="phone[1]" type="checkbox" value="foo" /> –
		<input name="phone[2]" type="checkbox" value="bar" /> –
		<input name="phone[3]" type="checkbox" value="zop" />
*/
{
	protected $name;

	protected $min;
	protected $max;
	
	protected $values;
	protected $errors;
	protected $fields = array();
	
	public function __construct($name, &$values, &$errors, $min, $max)
	{
		$this->name = $name;
		
		$this->min = $min;
		$this->max = $max;
		
		$this->values =& $values;
		$this->errors =& $errors;
		
		$limit = max($this->min, !is_null($this->max) ? min($this->max, count($this->values)) : count($this->values));
		$this->values = !is_array($this->values) ? array() : array_values($this->values);
		
		for ($i=0; $i < $limit; $i++) 
		{ 
			$this->fields[$i] = new aeFormField($name, $i, $this->values[$i], $this->errors[$i]);
		}
	}

	public function count()
	/*
		Returns the number of fields in the sequence.
	*/
	{
		return count($this->fields);
	}
	
	public function validate()
	/*
		Validates all fields in the sequence and returns TRUE or FALSE.
	*/
	{
		$count = 0;
		$result = true;
		$errors = array_map(array($this, '_validate'), $this->values);
		
		foreach ($errors as $key => $error) 
		{
			$count++;
			$result &= empty($error);
			$this->errors[$key] = $error;
		}
		
		return $result && $count >= $this->min && $count <= $this->max;
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
		return isset($this->fields[$offset]) ? $this->fields[$offset] : null;
	}
	
	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) 
		{
			$offset = empty($this->fields) ? 0 : max(array_keys($this->fields)) + 1;
		}
		else if (isset($this->fields[$offset]))
		{
			$this->fields[$offset]->value($value);
			
			return;
		}
		
		$this->values[$offset] = $value;
		$this->fields[$offset] = new aeFormField($this->name, $offset, $this->values[$offset], $this->errors[$offset]);
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
