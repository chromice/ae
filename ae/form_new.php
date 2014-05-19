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
// = Vairous interfaces =
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
	public function initial($values);
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
	
	public function required($message, $callback = null);
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
	const email = '([0-9a-zA-Z]([-\+\.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})';
	const url = '((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)';
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
		if (is_array($values) && !$this->form->is_submitted())
		{
			self::_merge_values($values, $this->values);
		}
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
			$is_valid &= $field->validate();
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
		foreach ($this->validators as $order => $func)
		{
			if ($order !== aeValidator::order_required && empty($this->value))
			{
				return true;
			}
			
			if ($this->multiple && $order !== aeValidator::order_required)
			{
				foreach ($this->value as $value)
				{
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
				$has_errors |= self::_has_errors($error);
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
		
		$flatten = function ($error) use (&$flatten, &$errors)
		{
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
		
		$errors = array_filter($errors);
		
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
		$this->validators[aeValidator::order_required] = function ($value, $index = null) use ($message, $callback)
		{
			if (is_callable($callback))
			{
				return $callback($value, $index) === false ? $message : null;
			}
			
			return empty($value) ? $message : null;
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
		$this->validators[aeTextValidator::order_valid_pattern] = function ($value) use ($message, $pattern, $time)
		{
			return preg_match('/^(?:' . $pattern . ')$/', $value) !== 1 
				|| $time && strtotime($value) === FALSE ? $message : null;
		};
		
		return $this;
	}
	
	public function min_length($message, $length)
	{
		$this->html['minlength'] = $length;
		$this->validators[aeTextValidator::order_min_length] = function ($value) use ($message, $length)
		{
			return strlen($value) < $length ? $message : null;
		};
		
		return $this;
	}

	public function max_length($message, $length)
	{
		$this->html['maxlength'] = $length;
		$this->validators[aeTextValidator::order_max_length] = function ($value) use ($message, $length)
		{
			return strlen($value) > $length ? $message : null;
		};
		
		return $this;
	}

	public function min_value($message, $limit)
	{
		$this->html['min'] = $limit;
		
		if (!empty($this->time_format))
		{
			$format = $this->time_format;
			$this->validators[aeTextValidator::order_min_value] = function ($value) use ($message, $limit, $format)
			{
				return preg_match($format, $value) !== 1 
					|| preg_match($format, $limit) !== 1
					|| strtotime($value) < strtotime($limit) ? $message : null;
			};
		}
		else
		{
			$this->validators[aeTextValidator::order_min_value] = function ($value) use ($message, $limit)
			{
				return $value < $limit ? $message : null;
			};
		}

		return $this;
	}
	
	public function max_value($message, $limit)
	{
		$this->html['max'] = $limit;
		
		if (!empty($this->time_format))
		{
			$format = $this->time_format;
			$this->validators[aeTextValidator::order_max_value] = function ($value) use ($message, $limit, $format)
			{
				return preg_match($format, $value) !== 1 
					|| preg_match($format, $limit) !== 1
					|| strtotime($value) > strtotime($limit) ? $message : null;
			};
		}
		else
		{
			$this->validators[aeTextValidator::order_max_value] = function ($value) use ($message, $limit)
			{
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
		$this->validators[aeValidator::order_required] = function ($file, $index = null) use ($message, $callback)
		{
			if (is_callable($callback))
			{
				return $callback($file, $index) === false ? $message : null;
			}
			
			return !is_a($file, 'aeFile') || $file->exists() ? $message : null;
		};
		
		return $this;
	}
	
	public function accept($message, $types)
	{
		if (!is_array($types))
		{
			$types = explode(',', $types);
		}
		
		$this->html['accept'] = implode(',', $types);
		
		$types = array_map(function ($type) {
			return trim($type, '* ');
		}, $types);
		
		$this->validators[aeFileValidator::order_accept] = function ($file) use ($types, $message) 
		{
			try
			{
				$type = preg_quote($file->type());
				$mimetype = preg_quote($file->mimetype());
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
				elseif ($type{0} !== '.' && strpos($mimetype, $_type) !== false)
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
		$this->validators[aeFileValidator::order_min_size] = function ($file) use ($size, $message)
		{
			return $file->size() < $size ? $message : null;
		};
		
		return $this;
	}
	
	public function max_size($message, $size)
	{
		$this->validators[aeFileValidator::order_max_size] = function ($file) use ($size, $message)
		{
			return $file->size() > $size ? $message : null;
		};
		
		return $this;
	}
	
	public function min_width($message, $width)
	{
		$this->validators[aeFileValidator::order_min_width] = function ($file) use ($width, $message)
		{
			return $file->width() < $width ? $message : null;
		};
		
		return $this;
	}
	
	public function max_width($message, $width)
	{
		$this->validators[aeFileValidator::order_max_width] = function ($file) use ($width, $message)
		{
			return $file->width() > $width ? $message : null;
		};
		
		return $this;
	}
	
	public function min_height($message, $height)
	{
		$this->validators[aeFileValidator::order_min_height] = function ($file) use ($height, $message)
		{
			return $file->height() < $height ? $message : null;
		};
		
		return $this;
	}
	
	public function max_height($message, $height)
	{
		$this->validators[aeFileValidator::order_max_height] = function ($file) use ($height, $message)
		{
			return $file->height() > $height ? $message : null;
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
		
		// Stop, if form has not been submitted
		if (!$this->is_submitted())
		{
			return;
		}
		
		// Set $_POST values.
		if (!empty($_POST) && is_array($_POST))
		{
			$this->values = $_POST;
		}
		
		ae::log('POSTed values:', $this->values);
		
		// Rearrange $_FILES array and merge it into values.
		if (!empty($_FILES) && is_array($_FILES))
		{
			$properties = array('name', 'type', 'tmp_name', 'error', 'size');
			$rearrange = function (&$array, $property, $key, $value) use (&$rearrange)
			{
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
					
					$is_scalar &= !is_array($structure[$p]);
				}
				
				if ($is_scalar)
				{
					$this->values[$name] = $structure;
					
					continue;
				}
				
				// Rearrange the structure
				foreach ($properties as $p)
				{
					foreach ($structure[$p] as $key => $value)
					{
						$rearrange($this->values[$name], $p, $key, $value);
					}
				}
			}
		}
		
		ae::log('POSTed + FILES values:', $this->values);
	}
	
	// =====================================
	// = aeFieldFactory implementation =
	// =====================================
	
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
	
	public function _has_files($value = true)
	{
		$this->has_files = $value;
	}
	
	// =====================================
	// = aeGroupFactory implementation =
	// =====================================
	
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
		return isset($_POST['__ae_form_id__']) && $_POST['__ae_form_id__'] === $this->id;
	}
	
	public function initial($values)
	{
		if (is_array($values) && !$this->form->is_submitted())
		{
			self::_merge_values($values, $this->values);
		}
	}
	
	public function validate()
	{
		$is_valid = isset($_POST['__ae_form_nonce__']) && $_POST['__ae_form_nonce__'] === $this->nonce;
		
		foreach ($this->fields as $index => $field)
		{
			if (is_a($field, 'aeGroupValueContainer') || is_a($field, 'aeFieldValueContainer'))
			{
				$is_valid &= $field->validate();
			}
		}
		
		return $is_valid;
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
				$output[] = ae::escape($name, ae::tag);
			}
			else
			{
				$output[] = ae::escape($name, ae::tag) . '="' . ae::escape($value, ae::attribute) . '"';
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
	
	// =====================================
	// = aeFieldFactory implementation =
	// =====================================
	
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
		aeFormGroupValueContainer;
	
	protected $name;
	protected $form;
	protected $fields = array();
	protected $values;
	protected $errors;
	
	protected $min;
	protected $max;
	
	public function __construct($name, &$form, &$values, &$errors, $min = 1, $max = null)
	{
		$this->name = $name;
		$this->form =& $form;
		$this->values =& $values;
		$this->errors =& $errors;
		
		$this->min = $min;
		$this->max = $max;
	}
	
	public function min()
	{
		return $this->min;
	}
	
	public function max()
	{
		return $this->max;
	}
	
	// =====================================
	// = aeFieldFactory implementation =
	// =====================================

	public function single($name)
	{
		return $this->fields[$name] = new aeFormTextFieldSequence($this->name . '[' . $name . ']', false, $this->form, $this->values[$name], $this->errors[$name], $this->min, $this->max);
	}
	
	public function multiple($name)
	{
		return $this->fields[$name] = new aeFormTextFieldSequence($this->name . '[' . $name . ']', true, $this->form, $this->values[$name], $this->errors[$name], $this->min, $this->max);
	}
	
	public function file($name, $destination)
	{
		$this->form->_has_files();
		
		return $this->fields[$name] = new aeFormFileFieldSequence($this->name . '[' . $name . ']', false, $destination, $this->form, $this->values[$name], $this->errors[$name], $this->min, $this->max);
	}
	
	public function files($name, $destination)
	{
		$this->form->_has_files();
		
		return $this->fields[$name] = new aeFormFileFieldSequence($this->name . '[' . $name . ']', true, $destination, $this->form, $this->values[$name], $this->errors[$name], $this->min, $this->max);
	}
	
	// ============================
	// = Countable implementation =
	// ============================
	
	public function count()
	{
		$base = reset($this->fields);
		
		return count($base);
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
			unset($sequence[$offset], $sequence[$offset], $sequence[$offset]);
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
		
		return $base->key();
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
		
		return $base->valid();
	}
}


abstract class aeFormFieldSequence implements ArrayAccess, Iterator, Countable, aeGroupErrorContainer, aeGroupValueContainer
{
	use aeFormGroupErrorContainer,
		aeFormGroupValueContainer,
		aeFormFieldValidator;
	
	protected $name;
	protected $multiple;
	protected $form;
	protected $fields = array();
	protected $values;
	protected $errors;
	
	protected $min;
	protected $max;
	
	
	protected $constructor;
	protected $validators = array();
	protected $html = array();
	
	public function __construct($name, $multiple, &$form, &$values, &$errors, &$min, &$max)
	{
		$this->name = $name;
		$this->multiple = $multiple;
		$this->form =& $form;
		$this->values =& $values;
		$this->errors =& $errors;
		
		$this->min =& $min;
		$this->max =& $max;
		
		$count = 0;
		$index = -1;
		$constructor = $this->constructor;
		
		if (!is_array($this->values))
		{
			$this->values = array();
		}
		
		foreach ($this->values as $index => $value)
		{
			if (!is_null($this->max) && $count > $this->max)
			{
				break;
			}
			
			$this->fields[$index] = $constructor($index, $this->validators, $this->html);
			
			$count++;
		}
		
		if ($count < $this->min)
		{
			for ($i = 0; $i < $this->min - $count; $i++)
			{ 
				$index++;
				$this->fields[$index] = $constructor($index, $this->validators, $this->html);
			}
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
		return isset($this->fields[$offset]);
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

class aeFormTextFieldSequence extends aeFormFieldSequence
{
	use aeFormTextFieldValidator;
	
	public function __construct($name, $multiple, &$form, &$values, &$errors, &$min, &$max)
	{
		$this->constructor = function ($index, &$validators, &$html) use ($name, $multiple, &$form, &$values, &$errors, &$validators, &$html)
		{
			return new aeFormTextField($name, $index, $multiple, $form, $values[$index], $errors[$index], $validators, $html);
		};
		
		parent::__construct($name, $multiple, $form, $values, $errors, $min, $max);
	}
}

class aeFormFileFieldSequence extends aeFormFieldSequence
{
	use aeFormFileFieldValidator;
	
	public function __construct($name, $multiple, $destination, &$form, &$values, &$errors, &$min, &$max)
	{
		$this->constructor = function ($index, &$validators, &$html) use ($name, $multiple, $destination, &$form, &$values, &$errors)
		{
			$form->_has_files();
		
			return new aeFormFileField($name, $index, $multiple, $destination, $form, $values[$index], $errors[$index], $validators, $html);
		};
		
		parent::__construct($name, $multiple, $form, $values, $errors, $min, $max);
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
	protected $multiple = false;
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
			. ae::escape($this->value, ae::attribute) 
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
				$output[] = '<option value="' . ae::escape($key, ae::attribute) . '"' 
					. ($this->_matches($key) ? ' selected' : '') . '>' . $value . '</option>';
			}
			elseif (is_array($value))
			{
				$output[] = '<optgroup label="' . ae::escape($key, ae::attribute) . '">' 
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

class aeFormFileField extends aeFormField implements aeFileValidator, aeGroupErrorContainer
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
			if (isset($this->value['tmp_name']) || isset($this->value['path']))
			{
				$this->value = $this->_parse_file($this->value);
			}
			
			if (!is_a($this->value, 'aeFile')/* || !$this->value->exists()*/)
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
				
				if (isset($value['tmp_name']) || isset($value['path']))
				{
					$this->value[$index] = self::_parse_file($value);
				}
				
				if (!is_a($this->value[$index], 'aeFile')/* || !$this->value[$index]->exists()*/)
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
			return ae::file(rtrim($this->destination, '/') . '/' . ltrim($file['path'], '/'), $file['full_name']);
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
				$this->error[] = $file_name . ' exceeds filesize limit.';
				return;
			default:
				$this->error[] = $file_name . ' could not be uploaded.';
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
			$target = rtrim($this->destination, '/') . '/' . $file->hash() . '.' . $file->type();
			
			$file->move($target);
		} 
		catch (aeFileException $e)
		{
			$this->error[] = $file_name . ' could not be uploaded.';
			
			return;
		}
		
		return $file;
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
		
		$output.= '<input ' . $this->_attributes($attributes) . ">\n";
		
		return $output;
	}
}