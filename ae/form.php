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
	
	public function __construct($form_id = null)
	{
		$this->id = $form_id;
	}

	public function single($name)
	{
		return new aeFormField($name);
	}

	public function multiple($name)
	{
		return new aeFormFieldMultiple($name);
	}

	public function sequence($name)
	{
		return new aeFormFieldSequence($name);
	}

	public function is_posted()
	{
		return isset($_POST['__ae_form_id__']) 
			&& $_POST['__ae_form_id__'] === $this->id;
	}

	public function validate()
	{
		return false;
	}

	public function errors()
	{
		return array();
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
			return '';
		}
		
		return $this;
	}

	public function values($values = null)
	{
		if (is_null($values))
		{
			return array();
		}
		
		return $this;
	}
}

class aeFormValidator
{
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
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	public function name()
	{
		return $name;
	}
	
	public function index()
	{
		return null;
	}
	
	public function value()
	{
		return '';
	}
	
	public function checked($value)
	{
		return false;
	}

	public function selected($value)
	{
		return false;
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	{
		return '';
	}
}

class aeFormFieldMultiple extends aeFormField
{
	public function __construct($name)
	{
		# code...
	}
	
}

class aeFormFieldSequence extends aeFormValidator
{
	protected $name;
	
	public function __construct($name)
	{
		$this->name;
	}

	public function count()
	{
		return 0;
	}
}
