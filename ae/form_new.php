<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2014 Anton Muraviev <chromice@gmail.com>
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

// Import dependancies
ae::import('ae/request.php');

// Define options
ae::options('ae.form', array(
	'novalidate' => false // whether to prevent HTML5 validation in browsers that support it.
));

// Invoke form object
ae::invoke('aeForm');

// ======================
// = Various interfaces =
// ======================

interface aeFieldFactory
{
	public function single($name);
	public function multiple($name);
	public function file($name, $destination);
	public function files($name, $destination);
}

interface aeGroupFactory
{
	public function group($name);
	public function sequence($name, $min = 1, $max = null);
}

interface aeGroupValueContainer
{
	public function initial($values);
	public function values();
	public function validate();
}

interface aeFieldValueContainer
{
	public function initial($value);
	public function value();
	public function validate();
}

interface aeGroupErrorContainer
{
	public function has_errors();
	public function errors($before = '<ul class="errors">', $after = '</ul>', $item_before = '<li>', $item_after = '</li>');
}

interface aeFieldErrorContainer
{
	public function has_error();
	public function error($before = '<em class="error">', $after = '</em>');
}

interface aeValidator
{
	const order_required = 0;
	const order_min_count = 5;
	const order_max_count = 6;
	
	public function required($message, $callback = null);
	public function min_count($message, $misc);
	public function max_count($message, $misc);
}

interface aeTextValidator extends aeValidator
{
	// Primitive patters
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
	
	// Complex patterns
	const email = '[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\-]+(?:\.[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?';
	const url = '(https?|ftp):\/\/(-\.)?([^\s\/?\.#-]+\.?)+(\/[^\s]*)?';
	const postcode_uk = '(([a-pr-uw-zA-PR-UW-Z]{1}[a-ik-yA-IK-Y]?)([0-9]?[a-hjks-uwA-HJKS-UW]?[abehmnprvwxyABEHMNPRVWXY]?|[0-9]?[0-9]?))\s*([0-9]{1}[abd-hjlnp-uw-zABD-HJLNP-UW-Z]{2})';
	
	// Validator order
	const order_valid_value   = 10;
	const order_valid_pattern = 20;
	const order_min_length    = 30;
	const order_max_length    = 31;
	const order_min_value     = 40;
	const order_max_value     = 41;
	
	// Text validators
	public function valid_value($message, $misc);
	public function valid_pattern($message, $pattern);
	public function min_length($message, $length);
	public function max_length($message, $length);
	public function min_value($message, $limit);
	public function max_value($message, $limit);
}

interface aeFileValidator extends aeValidator
{
	// Validator order
	const order_accept     = 10;
	const order_min_size   = 20;
	const order_max_size   = 21;
	const order_min_width  = 30;
	const order_max_width  = 32;
	const order_min_height = 31;
	const order_max_height = 33;
	
	// File validators
	public function accept($message, $types);
	public function min_size($message, $size);
	public function max_size($message, $size);
	public function min_width($message, $width);
	public function max_width($message, $width);
	public function min_height($message, $height);
	public function max_height($message, $height);
}


// ==================
// = Various traits =
// ==================

trait aeFormGroupValueContainer
{
	public function initial($values)
	{
		// If no values to set or form is submitted.
		if (!is_array($values) || empty($values) || $this->form->is_submitted())
		{
			return $this;
		}
		
		// If no fields were set up yet, simply merge values.
		if (empty($this->fields))
		{
			self::_merge_values($values, $this->values);
			
			return $this;
		}
		
		// Initialise field for each value, or simply merge them.
		foreach ($values as $_n => $_v)
		{
			if (isset($this->fields[$_n])
			&& (is_a($this->fields[$_n], 'aeGroupValueContainer')
			|| is_a($this->fields[$_n], 'aeFieldValueContainer')))
			{
				$this->fields[$_n]->initial($_v);
			}
			else
			{
				self::_merge_values($_v, $this->values[$_n]);
			}
		}
		
		return $this;
	}
	
	protected static function _merge_values($from, &$to)
	{
		if (!is_array($from))
		{
			$to = $from;
		}
		else 
		{
			if (!is_array($to))
			{
				$to = array();
			}
			
			foreach ($from as $key => $value)
			{
				self::_merge_values($value, $to[$key]);
			}
		}
	}
	
	public function values()
	{
		$values = array();
		
		foreach ($this->fields as $name => $field)
		{
			if (is_a($field, 'aeGroupValueContainer'))
			{
				$values[$name] = $field->values();
			}
			elseif (is_a($field, 'aeFieldValueContainer'))
			{
				$values[$name] = $field->value();
			}
		}
		
		return $values;
	}
	
	public function validate()
	{
		$is_valid = true;
		
		foreach ($this->fields as &$field)
		{
			if (is_a($field, 'aeGroupValueContainer') || is_a($field, 'aeFieldValueContainer'))
			{
				$is_valid = $field->validate() && $is_valid;
			}
		}
		
		return $is_valid;
	}
}

trait aeFormFieldValueContainer
{
	public function initial($value)
	{
		if (!$this->form->is_submitted())
		{
			$this->value = $value;
			$this->_normalize_value();
		}
		
		return $this;
	}
	
	protected function _normalize_value()
	{
		if ($this->multiple)
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
	
	public function value()
	{
		return $this->value;
	}
	
	public function validate()
	{
		$validators = $this->validators;
		ksort($validators);
		
		// Validate required, min_count and max_count constraints first.
		foreach (array(aeValidator::order_required, aeValidator::order_min_count, aeValidator::order_max_count) as $validator)
		{
			if (isset($validators[$validator])
			&& $error = $validators[$validator]($this->value, $this->index))
			{
				if (is_a($this, 'aeGroupErrorContainer'))
				{
					$this->errors[] = $error;
				}
				elseif (is_a($this, 'aeFieldErrorContainer'))
				{
					$this->error = $error;
				}
				
				return false;
			}
		
			unset($validators[$validator]);
		}
		
		// Check the rest of the constraints
		foreach ($validators as $func)
		{
			if ($this->multiple)
			{
				foreach ($this->value as $value)
				{
					if (empty($value))
					{
						continue;
					}
					
					if ($error = $func($value, $this->index))
					{
						if (is_a($this, 'aeGroupErrorContainer'))
						{
							$this->errors[] = $error;
						}
						elseif (is_a($this, 'aeFieldErrorContainer'))
						{
							$this->error = $error;
						}
						
						return false;
					}
				}
			}
			elseif (empty($this->value))
			{
				break;
			}
			elseif ($error = $func($this->value, $this->index))
			{
				if (is_a($this, 'aeGroupErrorContainer'))
				{
					$this->errors[] = $error;
				}
				elseif (is_a($this, 'aeFieldErrorContainer'))
				{
					$this->error = $error;
				}
				
				return false;
			}
		}
		
		return true;
	}
}

trait aeFormGroupErrorContainer
{
	public function has_errors()
	{
		return self::_has_errors($this->errors);
	}
	
	protected static function _has_errors($errors)
	{
		if (!is_array($errors))
		{
			return !empty($errors);
		}
		else
		{
			$has_errors = false;
		
			foreach ($errors as $error)
			{
				$has_errors = $has_errors || self::_has_errors($error);
			}
		
			return $has_errors;
		}
	}
	
	public function errors($before = '<ul class="errors">', $after = '</ul>', $item_before = '<li>', $item_after = '</li>')
	{
		if (is_null($before) && is_null($after) && is_null($item_before) && is_null($item_after))
		{
			return $this->errors;
		}
		
		$errors = array();
		
		$flatten = function ($error) use (&$flatten, &$errors) {
			if (!is_array($error))
			{
				$errors[] = $error;
				
				return;
			}
			else foreach ($error as $_error)
			{
				$flatten($_error);
			}
		};
		
		if (is_array($this->errors))
		{
			array_filter($this->errors, $flatten);
			$errors = array_filter($errors);
		}
		
		if (empty($errors))
		{
			return '';
		}
		
		return $before . $item_before . implode($item_after . ' ' . $item_before, $errors) . $item_after . $after;
	}
}

trait aeFormFieldErrorContainer
{
	public function has_error()
	{
		return !empty($this->error);
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	{
		return !empty($this->error) ? $before . $this->error . $after : '';
	}
}

trait aeFormGroupAccess
{
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

trait aeFormFieldValidator
{
	public function required($message, $callback = null)
	{
		$is_multiple = $this->multiple;
		
		$this->validators[aeValidator::order_required] = function ($value, $index = null) use ($message, $callback, $is_multiple) {
			$is_required = is_callable($callback) ? $callback($index) : true;
			$not_empty = function ($value) {
				return is_string($value) && strlen($value) > 0;
			};
			
			if (!$is_required)
			{
				return;
			}
			elseif (!$is_multiple)
			{
				return !$not_empty($value) ? $message : null;
			}
			else
			{
				return !is_array($value) || count(array_filter($value, $not_empty)) === 0 ? $message : null;
			}
		};
		
		return $this;
	}
	
	public function min_count($message, $misc)
	{
		if (!$this->multiple)
		{
			trigger_error('Cannot set min_count() constraint on a scalar field.', E_USER_ERROR);
		}
		
		$this->validators[aeValidator::order_min_count] = function ($value, $index = null) use ($message, $misc) {
			$min_count = (int) (is_callable($misc) ? $misc($index) : $misc);
			
			return !is_array($value) || count($value) < $min_count ? $message : null;
		};
		
		return $this;
	}
	
	public function max_count($message, $misc)
	{
		if (!$this->multiple)
		{
			trigger_error('Cannot set max_count() constraint on a scalar field.', E_USER_ERROR);
		}
		
		$this->validators[aeValidator::order_max_count] = function ($value, $index = null) use ($message, $misc) {
			$max_count = (int) (is_callable($misc) ? $misc($index) : $misc);
			
			return !is_array($value) || count($value) > $max_count ? $message : null;
		};
		
		return $this;
	}
}

trait aeFormTextFieldValidator
{
	public function valid_value($message, $misc)
	{
		$this->validators[aeTextValidator::order_valid_value] = function ($value, $index = null) use ($message, $misc) {
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
	{
		if (in_array($pattern, array(
			aeTextValidator::month,
			aeTextValidator::week,
			aeTextValidator::date,
			aeTextValidator::datetime,
			aeTextValidator::time
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
		$this->validators[aeTextValidator::order_valid_pattern] = function ($value) use ($message, $pattern, $time) {
			return preg_match('/^(?:' . $pattern . ')$/', $value) !== 1 
				|| $time && strtotime($value) === false ? $message : null;
		};
		
		return $this;
	}
	
	public function min_length($message, $length)
	{
		$message = str_replace('{length}', $length, $message);
		
		$this->html['minlength'] = $length;
		$this->validators[aeTextValidator::order_min_length] = function ($value) use ($message, $length) {
			return strlen($value) < $length ? $message : null;
		};
		
		return $this;
	}

	public function max_length($message, $length)
	{
		$message = str_replace('{length}', $length, $message);
		
		$this->html['maxlength'] = $length;
		$this->validators[aeTextValidator::order_max_length] = function ($value) use ($message, $length) {
			return strlen($value) > $length ? $message : null;
		};
		
		return $this;
	}

	public function min_value($message, $limit)
	{
		$message = str_replace('{value}', $limit, $message);
		
		$this->html['min'] = $limit;
		
		if (!empty($this->time_format))
		{
			$format = $this->time_format;
			$this->validators[aeTextValidator::order_min_value] = function ($value) use ($message, $limit, $format) {
				return preg_match($format, $value) !== 1 
					|| preg_match($format, $limit) !== 1
					|| strtotime($value) < strtotime($limit) ? $message : null;
			};
		}
		else
		{
			$this->validators[aeTextValidator::order_min_value] = function ($value) use ($message, $limit) {
				return $value < $limit ? $message : null;
			};
		}

		return $this;
	}
	
	public function max_value($message, $limit)
	{
		$message = str_replace('{value}', $limit, $message);
		
		$this->html['max'] = $limit;
		
		if (!empty($this->time_format))
		{
			$format = $this->time_format;
			$this->validators[aeTextValidator::order_max_value] = function ($value) use ($message, $limit, $format) {
				return preg_match($format, $value) !== 1 
					|| preg_match($format, $limit) !== 1
					|| strtotime($value) > strtotime($limit) ? $message : null;
			};
		}
		else
		{
			$this->validators[aeTextValidator::order_max_value] = function ($value) use ($message, $limit) {
				return $value > $limit ? $message : null;
			};
		}

		return $this;
	}
}

trait aeFormFileFieldValidator
{
	public function required($message, $callback = null)
	{
		// FIXME: This is almost a duplicate of aeFormFieldValidator::required()
		$is_multiple = $this->multiple;
		
		$this->validators[aeValidator::order_required] = function ($value, $index = null) use ($message, $callback, $is_multiple) {
			$is_required = is_callable($callback) ? $callback($index) : true;
			$not_empty = function ($value) {
				return is_a($value, 'aeFile') && $value->exists();
			};
			
			if (!$is_required)
			{
				return;
			}
			elseif (!$is_multiple)
			{
				return !$not_empty($value) ? $message : null;
			}
			else
			{
				return !is_array($value) || count(array_filter($value, $not_empty)) === 0 ? $message : null;
			}
		};
		
		return $this;
	}
	
	public function accept($message, $types)
	{
		if (!is_array($types))
		{
			$types = explode(',', $types);
		}
		
		// Trim any whitespace
		$types = array_map(function ($type) {
			return trim($type, ' ');
		}, $types);
		
		// Set accept attribute
		$this->html['accept'] = implode(',', $types);
		
		// Trim wildcard
		$types = array_map(function ($type) {
			return trim($type, '*');
		}, $types);
		
		$this->validators[aeFileValidator::order_accept] = function ($file) use ($types, $message) {
			try
			{
				$type = preg_quote($file->type());
				$mimetype = preg_quote($file->mimetype());
			}
			catch (aeFileException $e)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
			
			foreach ($types as $_type)
			{
				if ($_type{0} === '.' && '.' . $type === $_type)
				{
					return;
				}
				elseif ($_type{0} !== '.' && strpos($mimetype, $_type) !== false)
				{
					return;
				}
			}
			
			return str_replace('{file}', $file->full_name(false), $message);
		};
		
		return $this;
	}
	
	public function min_size($message, $size)
	{
		$message = str_replace('{size}', self::_format_size($size), $message);
		
		$this->validators[aeFileValidator::order_min_size] = function ($file) use ($size, $message) {
			if ($file->size() < $size)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
		};
		
		return $this;
	}
	
	public function max_size($message, $size)
	{
		$message = str_replace('{size}', self::_format_size($size), $message);
		
		$this->validators[aeFileValidator::order_max_size] = function ($file) use ($size, $message) {
			if ($file->size() > $size)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
		};
		
		return $this;
	}
	
	protected static function _format_size($bytes)
	{
		$units = array('byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte', 'petabyte', 'exabyte', 'zettabyte', 'yottabyte');
		$factor = floor((strlen(round($bytes)) - 1) / 3);
		$value = sprintf("%.2f", $bytes / pow(1024, $factor));
		
		return rtrim($value, '.0') . ' ' . @$units[$factor] . ($value === '1.00' ? '' : 's');
	}
	
	public function min_width($message, $width)
	{
		$message = str_replace('{width}', $width, $message);
		
		$this->validators[aeFileValidator::order_min_width] = function ($file) use ($width, $message) {
			if ($file->width() < $width)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
		};
		
		return $this;
	}
	
	public function max_width($message, $width)
	{
		$message = str_replace('{width}', $width, $message);
		
		$this->validators[aeFileValidator::order_max_width] = function ($file) use ($width, $message) {
			if ($file->width() > $width)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
		};
		
		return $this;
	}
	
	public function min_height($message, $height)
	{
		$message = str_replace('{height}', $height, $message);
		
		$this->validators[aeFileValidator::order_min_height] = function ($file) use ($height, $message) {
			if ($file->height() < $height)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
		};
		
		return $this;
	}
	
	public function max_height($message, $height)
	{
		$message = str_replace('{height}', $height, $message);
		
		$this->validators[aeFileValidator::order_max_height] = function ($file) use ($height, $message) {
			if ($file->height() > $height)
			{
				return str_replace('{file}', $file->full_name(false), $message);
			}
		};
		
		return $this;
	}
}

// ========
// = Form =
// ========

class aeForm implements ArrayAccess, aeFieldFactory, aeGroupFactory, aeGroupErrorContainer, aeGroupValueContainer
{
	use aeFormGroupAccess,
		aeFormGroupErrorContainer,
		aeFormGroupValueContainer;
	
	protected $id;
	protected $form;
	protected $method;
	protected $source;
	protected $nonces;
	protected $fields = array();
	protected $values = array();
	protected $errors = array();
	protected $has_files = false;
	protected $has_command = false;
	
	public function __construct($form_id, $method = 'post')
	{
		if (empty($form_id))
		{
			trigger_error('Form ID cannot be empty.', E_USER_ERROR);
		}
		
		$this->id = $form_id;
		$this->form =& $this;
		$this->method = strtolower($method);
		
		switch ($this->method)
		{
			case 'post':
				$this->source =& $_POST;
				break;
			case 'get':
				$this->source =& $_GET;
				break;
			default:
				throw new aeFormException('Unknown form method: ' . $method);
		}
		
		$this->nonces = ae::session('form-nonces');
		
		// Stop, if form has not been submitted
		if (!$this->is_submitted())
		{
			return;
		}
		
		// Set values.
		if (!empty($this->source) && is_array($this->source))
		{
			$this->values = $this->source;
		}
		
		if ($this->method === 'get' || empty($_FILES) || !is_array($_FILES))
		{
			return;
		}
		
		// Rearrange $_FILES array first
		$files = array();
		$properties = array('name', 'type', 'tmp_name', 'error', 'size');
		$rearrange = function (&$array, $property, $key, $value) use (&$rearrange) {
			if (!is_array($value))
			{
				if (!is_array($array))
				{
					$array[$key] = array($property => $value);
				}
				else
				{
					$array[$key][$property] = $value;
				}
			}
			else foreach ($value as $_key => $_value)
			{
				$rearrange($array[$key], $property, $_key, $_value);
			}
		};
		
		foreach ($_FILES as $name => $structure)
		{
			// Validate the structure first
			// and make sure rearrangement is required.
			$is_scalar = true;
			
			foreach ($properties as $p)
			{
				if (!array_key_exists($p, $structure))
				{
					continue 2;
				}
				
				$is_scalar = !is_array($structure[$p]) && $is_scalar;
			}
			
			if ($is_scalar)
			{
				$files[$name] = $structure;
				
				continue;
			}
			
			// Rearrange the structure
			foreach ($properties as $p)
			{
				foreach ($structure[$p] as $key => $value)
				{
					$rearrange($files[$name], $p, $key, $value);
				}
			}
		}
		
		// Append files
		$append_files = function ($from, &$to) use (&$append_files) {
			foreach ($from as $index => $value)
			{
				if (!is_array($value))
				{
					trigger_error('Could not merge file and value arrays.', E_USER_ERROR);
					return;
				}
		
				if (!isset($value['tmp_name']))
				{
					$append_files($value, $to[$index]);
				}
				else
				{
					if (!is_numeric($index))
					{
						$to[$index] = $value;
					}
					else if (!empty($to) && is_array($to))
					{
						$to[] = $value;
					}
					else
					{
						$to = array($index => $value);
					}
				}
			}
		};
		
		$append_files($files, $this->values);
	}
	
	public function _has_files()
	{
		if ($this->method !== 'post')
		{
			throw new aeFormException('The form must use POST method for file inputs to work.');
		}
		
		$this->has_files = true;
	}
	
	public function _has_command()
	{
		$this->has_command = true;
	}
	
	// =================================
	// = aeFieldFactory implementation =
	// =================================
	
	public function single($name)
	{
		return $this->fields[$name] = new aeFormTextField($name, null, false, $this, $this->values[$name], $this->errors[$name]);
	}
	
	public function multiple($name)
	{
		return $this->fields[$name] = new aeFormTextField($name, null, true, $this, $this->values[$name], $this->errors[$name]);
	}
	
	public function file($name, $destination)
	{
		$this->_has_files();
		
		return $this->fields[$name] = new aeFormFileField($name, null, false, $destination, $this, $this->values[$name], $this->errors[$name]);
	}
	
	public function files($name, $destination)
	{
		$this->_has_files();
		
		return $this->fields[$name] = new aeFormFileField($name, null, true, $destination, $this, $this->values[$name], $this->errors[$name]);
	}
	
	// =================================
	// = aeGroupFactory implementation =
	// =================================
	
	public function group($name)
	{
		return $this->fields[$name] = new aeFormGroup($name, $this, $this->values[$name], $this->errors[$name]);
	}
	
	public function sequence($name, $min = 1, $max = null)
	{
		return $this->fields[$name] = new aeFormSequence($name, $this, $this->values[$name], $this->errors[$name], $min, $max);
	}
	
	// ==============
	// = Form state =
	// ==============
	
	public function is_submitted()
	{
		return isset($this->source['__ae_form_id__']) && $this->source['__ae_form_id__'] === $this->id;
	}
	
	public function validate()
	{
		$is_valid = isset($this->source['__ae_form_nonce__']) && $this->source['__ae_form_nonce__'] === $this->nonces[$this->id];
		$has_command = $this->has_command || isset($this->source['__ae_command__']);
		
		if ($has_command && $is_valid)
		{
			return false;
		}
		
		foreach ($this->fields as $index => $field)
		{
			if (is_a($field, 'aeGroupValueContainer') || is_a($field, 'aeFieldValueContainer'))
			{
				$is_valid = $field->validate() && $is_valid;
			}
		}
		
		if ($is_valid)
		{
			$this->nonces[$this->id] = self::_generate_nonce();
		}
		
		return $is_valid;
	}
	
	protected static function _generate_nonce()
	{
		// FIXME: Is this a good enough random string with enough entropy?
		return md5(uniqid(mt_rand(), true));
	}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function id()
	{
		return $this->id;
	}
	
	public function open($attributes = array())
	{
		$attributes = array_merge(array(
			'action' => aeRequest::url(),
			'novalidate' => ae::options('ae.form')->get('novalidate')
		), $attributes);
		
		$attributes['id'] = $this->id() . '-form';
		$attributes['method'] = $this->method;
		
		if ($this->has_files)
		{
			$attributes['enctype'] = 'multipart/form-data';
		}
		
		// Get max file size
		$limits['post_max_size'] = @ini_get('post_max_size');
		$limits['upload_max_filesize'] = @ini_get('upload_max_filesize');
		
		// TODO: Array map this function instead: http://www.php.net/manual/en/function.ini-get.php#96996
		foreach ($limits as $key => $value)
		{
			if (preg_match('/^(\d+)(K|M|G)?$/i', trim($value), $parts) === 1)
			{
				$limit = (int) $parts[1];
				
				if (isset($parts[2])) switch (strtoupper($parts[2]))
				{
					case 'G':
						$limit*= 1024;
					case 'M':
						$limit*= 1024;
					case 'K':
						$limit*= 1024;
				}
				
				$limits[$key] = $limit;
			}
			else
			{
				unset($limits[$key]);
			}
		}
		
		if (empty($this->nonces[$this->id]))
		{
			$this->nonces[$this->id] = self::_generate_nonce();
		}
		
		return '<form ' . self::attributes($attributes) . '>'
			. '<input type="hidden" name="__ae_form_id__" value="' . $this->id . '" />'
			. '<input type="hidden" name="__ae_form_nonce__" value="' . $this->nonces[$this->id] . '" />'
			. '<input type="hidden" name="MAX_FILE_SIZE" value="' . (count($limits) > 0 ? min($limits) : 1024 * 1024) . '" />'
			. '<input type="submit" tabindex="-1" style="position:absolute; left:-999em; width:0; overflow:hidden">';
	}
	
	public function close()
	{
		return '</form>';
	}
	
	public static function attributes($attributes)
	{
		$output = array();
		
		foreach ($attributes as $name => $value)
		{
			if ($value === false || strlen($value) === 0)
			{
				continue;
			}
			
			if ($value !== false && in_array($name, array(
				'readonly', 'multiple', 'checked', 'disabled', 'selected',
				'required', 'novalidate', 'formnovalidate', 'autofocus'
			)) || $value === true)
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
}


// ================
// = Field groups =
// ================

class aeFormGroup implements ArrayAccess, aeFieldFactory, aeGroupErrorContainer, aeGroupValueContainer
{
	use aeFormGroupAccess,
		aeFormGroupErrorContainer,
		aeFormGroupValueContainer;
	
	protected $name;
	protected $form;
	protected $fields = array();
	protected $values;
	protected $errors;
	
	public function __construct($name, &$form, &$values, &$errors)
	{
		$this->name = $name;
		$this->form =& $form;
		$this->values =& $values;
		$this->errors =& $errors;
	}
	
	// =================================
	// = aeFieldFactory implementation =
	// =================================
	
	public function single($name)
	{
		return $this->fields[$name] = new aeFormTextField($this->name . '[' . $name . ']', null, false, $this->form, $this->values[$name], $this->errors[$name]);
	}
	
	public function multiple($name)
	{
		return $this->fields[$name] = new aeFormTextField($this->name . '[' . $name . ']', null, true, $this->form, $this->values[$name], $this->errors[$name]);
	}
	
	public function file($name, $destination)
	{
		$this->form->_has_files();
		
		return $this->fields[$name] = new aeFormFileField($this->name . '[' . $name . ']', null, false, $destination, $this->form, $this->values[$name], $this->errors[$name]);
	}
	
	public function files($name, $destination)
	{
		$this->form->_has_files();
		
		return $this->fields[$name] = new aeFormFileField($this->name . '[' . $name . ']', null, true, $destination, $this->form, $this->values[$name], $this->errors[$name]);
	}
}


class aeFormSequence implements ArrayAccess, Iterator, Countable, aeFieldFactory, aeGroupErrorContainer, aeGroupValueContainer
{
	use aeFormGroupErrorContainer,
		aeFormGroupValueContainer {
			initial as _initial;
		}
	
	protected $name;
	protected $form;
	protected $fields = array();
	protected $values;
	protected $errors;
	
	protected $constructors = array();
	
	protected $min;
	protected $max;
	protected $length;
	
	public function __construct($name, &$form, &$values, &$errors, $min = 1, $max = null)
	{
		$this->name = $name;
		
		$this->form =& $form;
		$this->values =& $values;
		$this->errors =& $errors;
		
		$this->min = $min;
		$this->max = $max;
		
		if (!is_array($this->values))
		{
			$this->values = array();
		}
		
		if (isset($this->values['__ae_length__']))
		{
			$this->length = (int) $this->values['__ae_length__'];
			unset($this->values['__ae_length__']);
		}
		else
		{
			$this->_estimate_length();
		}
		
		if (isset($this->values['__ae_add__']))
		{
			$this->form->_has_command();
			
			$add = (int) $this->values['__ae_add__'];
			unset($this->values['__ae_add__']);
			
			$this->_add_item($add);
		}
		elseif (isset($this->values['__ae_move__']))
		{
			$this->form->_has_command();
			
			$move = explode(',', $this->values['__ae_move__']);
			$move = array_map('trim', $move);
			unset($this->values['__ae_move__']);
			
			if (count($move) === 2)
			{
				$this->_move_item((int) $move[0], (int) $move[1]);
			}
		}
		elseif (isset($this->values['__ae_remove__']))
		{
			$this->form->_has_command();
			
			$remove = (int) $this->values['__ae_remove__'];
			unset($this->values['__ae_remove__']);
			
			$this->_remove_item($remove);
		}
		
		$this->length = max($this->min, $this->length);
		
		if (!is_null($this->max))
		{
			$this->length = min($this->max, $this->length);
		}
		
		$this->_normalize_values();
	}
	
	public function min_length()
	{
		return $this->min;
	}
	
	public function max_length()
	{
		return $this->max;
	}
	
	public function initial($values)
	{
		$this->_initial($values);
		$this->_normalize_values();
		$this->_estimate_length();
		$this->_construct_fields();
		
		return $this;
	}
	
	protected function _add_item($index)
	{
		$this->length += 1;
	}
	
	protected function _remove_item($index)
	{
		if (!empty($this->values) && is_array($this->values))
		{
			foreach ($this->values as &$_values)
			{
				if (is_array($_values))
				{
					unset($_values[$index]);
				}
			}
		}
		
		$this->length--;
	}
	
	protected function _move_item($from, $to)
	{
		if (empty($this->values) && !is_array($this->values))
		{
			return;
		}
		
		foreach ($this->values as &$_values)
		{
			
			if (is_array($_values) && isset($_values[$from]) && isset($_values[$to]))
			{
				ksort($_values);
				
				$out = array_splice($_values, $from, 1);
				array_splice($_values, $to, 0, $out);
			}
		}
	}
	
	protected function _normalize_values()
	{
		$values = $this->values;
		$arrays = array_filter(array_map('is_array', $values));
		
		if (count($arrays) !== count($values) || count($arrays) === 0)
		{
			return;
		}
		
		$vectors = array_map('array_keys', array_values($values));
		$indexes = array_unique(call_user_func_array('array_merge', $vectors), SORT_NUMERIC);
		
		$index = 0;
		$this->values = array();
		
		foreach ($indexes as $_index)
		{
			foreach ($values as $name => $_values)
			{
				if (isset($_values[$_index]))
				{
					$this->values[$name][$index] = $_values[$_index];
				}
			}
			
			$index++;
		}
	}
	
	protected function _estimate_length()
	{
		if (is_null($this->length))
		{
			$this->length = $this->min;
		}
		
		foreach ($this->values as $name => $_values)
		{
			if (!is_array($_values))
			{
				continue;
			}
			
			$this->length = max($this->min, $this->length, count($_values));
		
			if (!is_null($this->max))
			{
				$this->length = min($this->max, $this->length);
			}
		}
	}
	
	protected function _construct_fields()
	{
		foreach (array_keys($this->constructors) as $name)
		{
			$this->_contruct_field($name);
		}
	}
	
	protected function _contruct_field($name)
	{
		if (!isset($this->constructors[$name]))
		{
			return;
		}
		
		if (isset($this->fields[$name]))
		{
			unset($this->fields[$name]);
		}
		
		return $this->fields[$name] = $this->constructors[$name]->__invoke($name);
	}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function add_button($offset = -1, $label = 'Add another', $attributes = array())
	{
		if (!is_array($attributes))
		{
			$attributes = array();
		}
		
		$attributes['type'] = 'submit';
		$attributes['name'] = $this->name . '[__ae_add__]';
		$attributes['value'] = $offset;
		
		$button = '<button ' . aeForm::attributes($attributes) . '>'
			. $label . '</button>';
		
		$counter = '<input ' . aeForm::attributes(array(
			'type' => 'hidden',
			'name' => $this->name . '[__ae_length__]',
			'value' => $this->length
		)) . '>';
		
		return $counter . (!is_null($this->max) && $this->length >= $this->max ? '' : $button);
	}
	
	public function move_button($from, $to, $label = null, $attributes = array())
	{
		if (is_null($label))
		{
			$label = $from > $to ? 'Move up' : 'Move down';
		}
		
		if ($from < 0 || $from >= $this->count() || $to < 0 || $to >= $this->count())
		{
			return;
		}
		
		if (!is_array($attributes))
		{
			$attributes = array();
		}
		
		$attributes['type'] = 'submit';
		$attributes['name'] = $this->name . '[__ae_move__]';
		$attributes['value'] = $from . ',' . $to;
		
		return '<button ' . aeForm::attributes($attributes) . '>' . $label . '</button>';
	}
	
	public function remove_button($offset, $label = 'Remove', $attributes = array())
	{
		if (!is_array($attributes))
		{
			$attributes = array();
		}
		
		$attributes['type'] = 'submit';
		$attributes['name'] = $this->name . '[__ae_remove__]';
		$attributes['value'] = $offset;
		
		return '<button ' . aeForm::attributes($attributes) . '>' . $label . '</button>';
	}
	
	// =================================
	// = aeFieldFactory implementation =
	// =================================

	public function single($name)
	{
		$parent =& $this;
		
		$this->constructors[$name] = function ($name) use (&$parent) {
			return new aeFormTextFieldSequence($parent->name . '[' . $name . ']', false, $parent->form, $parent->values[$name], $parent->errors[$name], $parent->length);
		};
		
		return $this->_contruct_field($name);
	}
	
	public function multiple($name)
	{
		$parent =& $this;
		
		$this->constructors[$name] = function ($name) use (&$parent) {
			return new aeFormTextFieldSequence($parent->name . '[' . $name . ']', true, $parent->form, $parent->values[$name], $parent->errors[$name], $parent->length);
		};
		
		return $this->_contruct_field($name);
	}
	
	public function file($name, $destination)
	{
		$parent =& $this;
		
		$this->constructors[$name] = function ($name) use (&$parent, $destination) {
			$parent->form->_has_files();
			
			return new aeFormFileFieldSequence($parent->name . '[' . $name . ']', false, $destination, $parent->form, $parent->values[$name], $parent->errors[$name], $parent->length);
		};
		
		return $this->_contruct_field($name);
	}
	
	public function files($name, $destination)
	{
		$parent =& $this;
		
		$this->constructors[$name] = function ($name) use (&$parent, $destination) {
			$parent->form->_has_files();
			
			return new aeFormFileFieldSequence($parent->name . '[' . $name . ']', true, $destination, $parent->form, $parent->values[$name], $parent->errors[$name], $parent->length);
		};
		
		return $this->_contruct_field($name);
	}
	
	// ============================
	// = Countable implementation =
	// ============================
	
	public function count()
	{
		$base = reset($this->fields);
		
		return $base->count();
	}
	
	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($offset)
	{
		$base = reset($this->fields);
		
		return isset($base[$offset]);
	}
	
	public function offsetGet($offset)
	{
		$result = array();
		
		foreach ($this->fields as $name => $sequence)
		{
			$result[$name] = $sequence[$offset];
		}
		
		return $result;
	}
	
	public function offsetSet($offset, $value)
	{
		trigger_error('Form fields cannot be set directly. Please use an appropriate factory method.', E_USER_ERROR);
	}
	
	public function offsetUnset($offset)
	{
		foreach ($this->fields as &$sequence)
		{
			unset($sequence[$offset]);
		}
	}
	
	// ===========================
	// = Iterator implementation =
	// ===========================
	
	public function rewind()
	{
		foreach ($this->fields as &$sequence)
		{
			$sequence->rewind();
		}
	}
	
	public function current()
	{
		$result = array();
		
		foreach ($this->fields as $name => &$sequence)
		{
			$result[$name] = $sequence->current();
		}
		
		return $result;
	}
	
	public function key()
	{
		$base = reset($this->fields);
		
		if (is_a($base, 'aeFormFieldSequence'))
		{
			return $base->key();
		}
	}
	
	public function next()
	{
		foreach ($this->fields as &$sequence)
		{
			$sequence->next();
		}
	}
	
	public function valid()
	{
		$base = reset($this->fields);
		
		if (is_a($base, 'aeFormFieldSequence'))
		{
			return $base->valid();
		}
		
		return false;
	}
}


abstract class aeFormFieldSequence implements ArrayAccess, Iterator, Countable, aeValidator, aeGroupErrorContainer, aeGroupValueContainer
{
	use aeFormFieldValidator,
		aeFormGroupErrorContainer,
		aeFormGroupValueContainer;
	
	protected $name;
	protected $multiple;
	protected $form;
	protected $fields = array();
	protected $values;
	protected $errors;
	
	protected $length;
	
	protected $constructor;
	protected $validators = array();
	protected $html = array();
	
	public function __construct($name, $multiple, &$form, &$values, &$errors, &$length)
	{
		$this->name = $name;
		$this->multiple = $multiple;
		
		$this->form =& $form;
		$this->values =& $values;
		$this->errors =& $errors;
		
		$this->length =& $length;
		
		if (!is_array($this->values))
		{
			$this->values = array();
		}
		
		for ($index = 0; $index < $this->length; $index++)
		{ 
			$this->fields[$index] = $this->constructor->__invoke($index, $this->validators, $this->html);
		}
	}
	
	// ============================
	// = Countable implementation =
	// ============================
	
	public function count()
	{
		return count($this->fields);
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
		return $this->fields[$offset];
	}
	
	public function offsetSet($offset, $value)
	{
		trigger_error('Form fields cannot be set directly. Please use an appropriate factory method.', E_USER_ERROR);
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

class aeFormTextFieldSequence extends aeFormFieldSequence implements aeTextValidator
{
	use aeFormTextFieldValidator;
	
	public function __construct($name, $multiple, &$form, &$values, &$errors, &$length)
	{
		$this->constructor = function ($index, &$validators, &$html) use ($name, $multiple, &$form, &$values, &$errors) {
			return new aeFormTextField($name, $index, $multiple, $form, $values[$index], $errors[$index], $validators, $html);
		};
		
		parent::__construct($name, $multiple, $form, $values, $errors, $length);
	}
}

class aeFormFileFieldSequence extends aeFormFieldSequence implements aeFileValidator
{
	use aeFormFileFieldValidator;
	
	public function __construct($name, $multiple, $destination, &$form, &$values, &$errors, &$length)
	{
		$this->constructor = function ($index, &$validators, &$html) use ($name, $multiple, $destination, &$form, &$values, &$errors) {
			$form->_has_files();
			
			return new aeFormFileField($name, $index, $multiple, $destination, $form, $values[$index], $errors[$index], $validators, $html);
		};
		
		parent::__construct($name, $multiple, $form, $values, $errors, $length);
	}
}


// ===============
// = Form fields =
// ===============

abstract class aeFormField implements aeValidator, aeFieldValueContainer
{
	use aeFormFieldValueContainer,
		aeFormFieldValidator;
	
	protected $name;
	protected $index;
	protected $multiple;
	protected $form;
	protected $value;
	
	protected $time_format;
	protected $validators = array();
	protected $html = array();
	
	public function __construct($name, $index, $multiple, &$form, &$value, &$validators = null, &$html = null)
	{
		$this->name = $name;
		$this->index = $index;
		$this->multiple = $multiple;
		$this->form =& $form;
		$this->value =& $value;
		
		if (!is_null($validators))
		{
			$this->validators =& $validators;
		}
		
		if (!is_null($html))
		{
			$this->html =& $html;
		}
		
		$this->_normalize_value();
	}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function id()
	{
		return trim($this->form->id() . '-' . preg_replace('/[\s_\[\]]+/', '-', $this->name), '-')
			. (!is_null($this->index) ? '-' . $this->index : '');
	}
	
	public function name()
	{
		return $this->name
			. (!is_null($this->index) ? '[' . $this->index . ']' : '')
			. ($this->multiple ? '[]' : '');
	}
	
	public function index()
	{
		return $this->index;
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
}

class aeFormTextField extends aeFormField implements aeTextValidator, aeFieldErrorContainer
{
	use aeFormFieldErrorContainer,
		aeFormTextFieldValidator;
	
	protected $error;
	protected $time_format;
	
	public function __construct($name, $index, $multiple, &$form, &$value, &$error, &$validators = null, &$html = null)
	{
		parent::__construct($name, $index, $multiple, $form, $value, $validators, $html);
		
		$this->error =& $error;
	}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function id($value = null)
	{
		return parent::id() . (!empty($value) ? '-' . $value : '');
	}
	
	public function input($type, $value = null, $attributes = array())
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
				$attributes['id'] = $this->id($value);
			}
		
			$attributes['checked'] = $this->_matches(!is_null($value) ? $value : 'on') ? 'checked' : '';
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
		$attributes['value'] = !is_null($value) ? $value : (!$this->multiple && $type === 'checkbox' ? 'on' : $this->value);
	
		return '<input ' . $this->_attributes($attributes) . '>';
	}
	
	public function textarea($attributes = array())
	{
		$attributes['required'] = isset($attributes['required']) 
			? !empty($attributes['required'])
			: !empty($this->html['required']);
	
		if (!empty($this->html['maxlength']))
		{
			$attributes['maxlength'] = $this->html['maxlength'];
		}
		
		if (!empty($this->html['minlength']))
		{
			$attributes['minlength'] = $this->html['minlength'];
		}
	
		return '<textarea ' . $this->_attributes($attributes) . '>'
			. ae::escape($this->value, ae::value)
			. '</textarea>';
	}
	
	public function select($options, $attributes = array())
	{
		$attributes['required'] = isset($attributes['required']) 
			? !empty($attributes['required'])
			: !empty($this->html['required']);
		$attributes['multiple'] = $this->multiple;
		
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
	
	protected function _matches($value)
	{
		$value = (string) $value;
		
		return $this->multiple ? in_array($value, $this->value) : $this->value === $value;
	}
}

class aeFormFileField extends aeFormField implements aeFileValidator, aeFieldErrorContainer, aeGroupErrorContainer
{
	use aeFormGroupErrorContainer,
		aeFormFileFieldValidator;
	
	protected $errors;
	protected $destination;
	
	public function __construct($name, $index, $multiple, $destination, &$form, &$value, &$errors, &$validators = null, &$html = null)
	{
		$this->errors =& $errors;
		$this->destination = ae::resolve($destination, false);
		
		parent::__construct($name, $index, $multiple, $form, $value, $validators, $html);
	}
	
	protected function _normalize_value()
	{
		if (!$this->multiple)
		{
			if (is_array($this->value) && (isset($this->value['tmp_name']) || isset($this->value['path'])))
			{
				if (isset($this->value['__ae_remove__']))
				{
					$this->form->_has_command();
					$this->value = null;
				}
				else
				{
					$this->value = $this->_parse_file($this->value);
				}
			}
			
			if (!is_a($this->value, 'aeFile'))
			{
				$this->value = null;
			}
		}
		else
		{
			if (!is_array($this->value))
			{
				$this->value = array();
				
				return;
			}
			
			foreach ($this->value as $index => $value)
			{
				if (!is_integer($index))
				{
					$this->value = array();
					
					return;
				}
				
				if (is_array($value) && (isset($value['tmp_name']) || isset($value['path'])))
				{
					if (isset($value['__ae_remove__']))
					{
						$this->form->_has_command();
						unset($this->value[$index]);
						
						continue;
					}
					else
					{
						$this->value[$index] = self::_parse_file($value);
					}
				}
				
				if (!is_a($this->value[$index], 'aeFile'))
				{
					unset($this->value[$index]);
				}
			}
		}
	}
	
	protected function _parse_file($file)
	{
		if (!empty($file['path']) && !empty($file['full_name']))
		{
			// FIXME: File may be located somewhere else.
			$path = rtrim($this->destination, '/') . '/' . ltrim($file['path'], '/');
			$full_name = $file['full_name'];
			
			unset($file['path'], $file['full_name']);
			
			return ae::file($path, $full_name, $file);
		}
		
		// Check if all necessary data is there
		if (!isset($file['tmp_name']) || !isset($file['name']) || !isset($file['error']))
		{
			return;
		}
		
		$file_name = (!empty($file['name']) ? $file['name'] : 'File');
		
		// Check if file is uploaded at all
		switch ($file['error'])
		{
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				return;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$this->errors[] = $file_name . ' exceeds filesize limit.';
				return;
			default:
				$this->errors[] = $file_name . ' could not be uploaded.';
				return;
		}
		
		$file = ae::file($file['tmp_name'], $file['name']);
		
		// Check if the file is indeed uploaded
		if (!$file->is_uploaded())
		{
			return;
		}
		
		try
		{
			// Move file to the destination
			$target = rtrim($this->destination, '/') . '/' . $file->name() . '.' . $file->type();
			
			$file->move($target);
		} 
		catch (aeFileException $e)
		{
			$this->errors[] = $file_name . ' could not be uploaded.';
			
			return;
		}
		
		return $file;
	}
	
	public function validate()
	{
		// FIXME: This is almost a duplicate of aeFormFieldValueContainer::validate()
		$validators = $this->validators;
		$errors = &$this->errors;
		ksort($validators);
		
		// Validate required, min_count and max_count constraints first.
		foreach (array(aeValidator::order_required, aeValidator::order_min_count, aeValidator::order_max_count) as $validator)
		{
			if (isset($validators[$validator])
			&& $error = $validators[$validator]($this->value, $this->index))
			{
				$errors[] = $error;
			
				return false;
			}
		
			unset($validators[$validator]);
		}
		
		// Validate other constraints.
		$is_valid = true;
		$validate = function ($value, $index) use (&$is_valid, &$errors, $validators)
		{
			// FIXME: Should we check if it's a file that exists here as well?
			if (empty($value))
			{
				return;
			}
			
			foreach ($validators as $func)
			{
				if ($error = $func($value, $index))
				{
					$errors[] = $error;
					$is_valid = false;
				}
			}
		};
		
		if ($this->multiple)
		{
			foreach ($this->value as $value)
			{
				$validate($value, $this->index);
			}
		}
		else
		{
			$validate($this->value, $this->index);
		}
		
		return $is_valid;
	}
	
	// ===============
	// = HTML output =
	// ===============
	
	public function name($offset = null)
	{
		return $this->name 
			. (!is_null($this->index) ? '[' . $this->index . ']' : '')
			. ($this->multiple ? '[' . $offset . ']' : '');
	}
	
	public function input($attributes = array())
	{
		if ($this->multiple)
		{
			$files = $this->value;
		}
		else
		{
			$files = array($this->value);
		}
		
		$file_offset = 0;
		$output = '';
		
		// Add uploaded file data as hidden inputs.
		foreach ($files as $index => $file)
		{
			if (!is_a($file, 'aeFile') || !$file->exists())
			{
				continue;
			}
			
			$full_name = $file->full_name(false);
			$meta = $file->meta();
			
			if ($file_offset > 0)
			{
				$output.= ', ';
			}
			
			$output.= '<span class="file">' . $full_name;
			$output.= '<input ' . aeForm::attributes(array(
				'type'  => 'hidden',
				'name'  => $this->name($file_offset) . '[full_name]',
				'value' => $full_name
			)) . '>';
			$output.= '<input ' . aeForm::attributes(array(
				'type'  => 'hidden',
				'name'  => $this->name($file_offset) . '[path]',
				'value' => str_replace($this->destination, '', $file->path())
			)) . '>';
			
			foreach ($meta as $key => $value)
			{
				if (is_scalar($key) && is_scalar($value))
				{
					$output.= '<input ' . aeForm::attributes(array(
						'type'  => 'hidden',
						'name'  => $this->name($file_offset) . '[' . $key . ']',
						'value' => $value
					)) . '>';
				}
			}
			
			$output.= ' <button ' . aeForm::attributes(array(
				'type' => 'submit',
				'name' => $this->name($file_offset) . '[__ae_remove__]',
				'value' => '1'
			)) . '>Remove</button>';
			
			$output.= '</span>';
			
			$file_offset++;
		}
		
		// Don't render input field, if single file's already uploaded.
		if (!$this->multiple && $file_offset > 0)
		{
			return $output;
		}
		
		// Render file input.
		$attributes['type'] = 'file';
		$attributes['multiple'] = $this->multiple;
		$attributes = array_merge($this->html, $attributes);
		
		$output.= '<input ' . $this->_attributes($attributes) . ">\n";
		
		return $output;
	}
	
	// ========================================
	// = aeFieldErrorContainer implementation =
	// ========================================
	
	public function has_error()
	{
		return $this->has_errors();
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	{
		return $this->errors($before, $after, null, null);
	}
}

class aeFormException extends aeException {}