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
	public function __construct($form_id = null)
	{
		# code...
	}

	public function single($name)
	{
		# code...
	}

	public function multiple($name)
	{
		# code...
	}

	public function sequence($name)
	{
		# code...
	}

	public function is_posted()
	{
		# code...
	}

	public function validate()
	{
		# code...
	}

	public function errors()
	{
		# code...
	}

	public function open()
	{
		# code...
	}

	public function close()
	{
		# code...
	}

	public function value($name, $value = null)
	{
		# code...
	}

	public function values($values = null)
	{
		# code...
	}
}

class aeFormValidator
{
	public function required($message)
	{
		# code...
	}
	
	public function format($message, $format)
	{
		# code...
	}
	
	public function min_length($message, $length)
	{
		# code...
	}
	
	public function max_length($message, $length)
	{
		# code...
	}
	
	public function min_value($message, $limit)
	{
		# code...
	}
	
	public function max_value($message, $limit)
	{
		# code...
	}
}

class aeFormField extends aeFormValidation
{
	public function __construct($name)
	{
		# code...
	}
	
	public function name()
	{
		# code...
	}
	
	public function index()
	{
		# code...
	}
	
	public function value()
	{
		# code...
	}
	
	public function checked($value)
	{
		# code...
	}

	public function selected($value)
	{
		# code...
	}
	
	public function error($before = '<em class="error">', $after = '</em>')
	{
		# code...
	}
}

class aeFormMultipleFields extends aeFormField
{
	public function __construct($name)
	{
		# code...
	}
	
}

class aeFormFieldSequence extends aeFormValidation
{
	public function __construct($name)
	{
		# code...
	}

	public function count()
	{
		# code...
	}
}
