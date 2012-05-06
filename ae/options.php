<?php if (!class_exists('ae')) exit;

#
# Copyright 2011 Anton Muraviev <chromice@gmail.com>
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

ae::invoke('aeOptions');

class aeOptions
/*
	Provides simple interface for library and module configuration.
	
		$lib_options = ae::options('library');
		
		$lib_options->set('bar','foo');
		
		echo $lib_options->get('bar', 'default value'); // 'foo'
		echo $lib_options->get('foo', 'default value'); // 'default value'
	
*/
{
	protected static $options;
	protected $reference;
	
	public function __construct($namespace = null)
	{
		if (is_string($namespace))
		{
			$this->reference =& self::$options[$namespace];
		}
		else
		{
			$this->reference =& self::$options;
		}
	}
	
	public function get($option, $default = null)
	{
		if (isset($this->reference[$option]))
		{
			return $this->reference[$option];
		}
		
		return $default;
	}
	
	public function set($option, $value)
	{
		$this->reference[$option] = $value;

		return $this;
	}
}