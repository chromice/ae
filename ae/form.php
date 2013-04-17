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
{
	// Numbers
	const valid_number = 1;
	const valid_integer = 3;
	const valid_octal = 5;
	const valid_hexadecimal = 9;
	const valid_decimal = 17;
	const valid_float = 33;
	
	// Misc
	const valid_email = 64;
	const valid_url = 128;
	const valid_url_path = 384;
	const valid_url_query = 640;
	const valid_ip = 1024;
	const valid_ipv4 = 3072;
	const valid_ipv6 = 5120;
	const valid_public_ip = 9216;
	
	protected $id;
	protected $fields = array();
	protected $values = array();
	protected $errors = array();
	
	public function __construct($form_id = null)
	{
		$this->id = $form_id;
		
		if ($this->is_posted())
		{
			$this->values($_POST);
		}
	}

	public function single($name)
	{
		return $this->fields[$name] = new aeFormField($name, null, $this->values[$name], $this->errors[$name]);
	}

	public function multiple($name)
	{
		return $this->fields[$name] = new aeFormFieldMultiple($name, $this->values[$name], $this->errors[$name]);
	}

	public function sequence($name, $min = 1, $max = null)
	{
		return $this->fields[$name] = new aeFormFieldSequence($name, $this->values[$name], $this->errors[$name], $min, $max);
	}

	public function is_posted()
	{
		return isset($_POST['__ae_form_id__']) 
			&& $_POST['__ae_form_id__'] === $this->id;
	}

	public function validate()
	{
		$result = true;
		
		foreach ($this->fields as $field)
		{
			$result &= $field->validate();
		}
		
		return $result;
	}

	public function errors()
	{
		return $this->errors;
	}

	public function open($action = '')
	{
		return '<form action="' . $action . '" method="post">'
			. '<input type="hidden" name="__ae_form_id__" value="' . $this->id . '" />';
	}

	public function close()
	{
		return '</form>';
	}

	public function value($name, $value = null)
	{
		if (is_null($value))
		{
			return isset($this->values[$name]) ? $this->values[$name] : null;
		}
		
		$this->values[$name] = $value;
		
		return $this;
	}

	public function values($values = null)
	{
		if (is_null($values))
		{
			return array_intersect_key($this->values, $this->fields);
		} 
		
		if (is_array($values))
		{
			$this->values = array_merge($this->values, $values);
		}
		
		return $this;
	}
}

class aeFormValidator
{
	protected $required;
	protected $validators = array();
	
	protected function _validate($value)
	{
		if (!empty($this->required) && empty($value))
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
	{
		$this->required = $message;
		
		return $this;
	}
	
	public function format($message, $format)
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
			$filter = FILTER_VALIDATE_URL;
			$options = ($format & aeForm::valid_url_query ? FILTER_FLAG_QUERY_REQUIRED : 0)
				| ($format & aeForm::valid_url_path ? FILTER_FLAG_PATH_REQUIRED : 0);
		}
		else if ($format & aeForm::valid_ip)
		{
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
	{
		$this->validators[] = function($value) use ($message, $length) {
			return strlen($value) < $length ? $message : null;
		};

		return $this;
	}
	
	public function max_length($message, $length)
	{
		$this->validators[] = function($value) use ($message, $length) {
			return strlen($value) > $length ? $message : null;
		};

		return $this;
	}
	
	public function min_value($message, $limit)
	{
		$this->validators[] = function($value) use ($message, $limit) {
			return $value < $limit ? $message : null;
		};

		return $this;
	}
	
	public function max_value($message, $limit)
	{
		$this->validators[] = function($value) use ($message, $limit) {
			return $value > $limit ? $message : null;
		};

		return $this;
	}

	public function options($message, $options)
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
{
	protected $name;
	protected $index;
	protected $value;
	protected $error;
	
	public function __construct($name, $index, &$value, &$error)
	{
		$this->name = $name;
		$this->index = $index;
		$this->value =& $value;
		$this->error =& $error;
	}
	
	public function name()
	{
		return $this->name;
	}
	
	public function index()
	{
		return $this->index;
	}
	
	public function value($value = null)
	{
		if (is_null($value))
		{
			return $this->value;
		}
		
		$this->value = $value;
		
		return $this;
	}
	
	public function checked($value)
	{
		return $this->_matches($value) ? 'checked' : '';
	}

	public function selected($value)
	{
		return $this->_matches($value) ? 'selected' : '';
	}
	
	public function validate()
	{
		$this->error = $this->_validate($this->value);
		
		return empty($this->error);
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	{
		return !empty($this->error) ? $before . $this->error . $after : '';
	}
	
	protected function _matches($value)
	{
		return is_scalar($this->value) && (string) $this->value === (string) $value;
	}
}

class aeFormFieldMultiple extends aeFormField
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
	
	public function name()
	{
		return $this->name;
	}
	
	public function count()
	{
		return count($this->fields);
	}
	
	public function validate()
	{
		$result = true;
		$errors = array_map(array($this, '_validate'), $this->values);
		
		foreach ($errors as $key => $error) 
		{
			$result &= empty($error);
			$this->errors[$key] = $error;
		}
		
		return $result;
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
