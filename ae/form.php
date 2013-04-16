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
	const valid_number = 0;
	const valid_integer = 1;
	const valid_octal = 2;
	const valid_hexadecimal = 3;
	const valid_decimal = 4;
	const valid_float = 5;
	
	// Formats
	const valid_email = 0;
	const valid_url = 0;
	const valid_url_path = 0;
	const valid_url_query = 0;
	const valid_ip = 0;
	const valid_ipv4 = 0;
	const valid_ipv6 = 0;
	const valid_pivate_ip = 0;
	const valid_public_ip = 0;
	
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
	protected $validators;
	
	public function validate($value)
	{
		
	}
	
	public function required($message)
	{
		return $this;
	}
	
	public function format($message, $format)
	{
		return $this;
	}
	
	public function min_length($message, $length)
	{
		return $this;
	}
	
	public function max_length($message, $length)
	{
		return $this;
	}
	
	public function min_value($message, $limit)
	{
		return $this;
	}
	
	public function max_value($message, $limit)
	{
		return $this;
	}

	public function options($message, $options)
	{
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
		return true;
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
	
	public function validate()
	{
		return true;
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
		return true;
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
			$this->values[$offset] = $value;
			$this->fields[$offset] = new aeFormField($this->name, $offset, $this->values[$offset], $this->errors[$offset]);
		}
		else
		{
			$this->fields[$offset]->value($value);
		}
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
		return current($this->fields) !== false;
	}
}
