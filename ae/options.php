<?php

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

namespace ae;

\ae::invoke('\ae\Options');

class Options
/*
	A simple key/value storage class used for configuration:
		
		// Set options
		$lib_options = ae::options('library');
		$lib_options->set('bar', 'foo');
		
		// Use options
		$lib_options_copy = ae::options('library');
		echo $lib_options_copy->get('bar'); // echoes 'foo'
	
	Preferably you should provide an exhaustive list of keys/default values
	via second argument:
	
		// Define all options
		ae::options('library', [
			'bar' => 'foo'
		]);
		
		// Use the defined options
		$options = ae::options('library');
		
		echo $options->get('bar'); // echoes 'foo'
		$options->set('bar', 'bar');
		echo $options->get('bar'); // echoes 'bar'
	
	This method leaves no room for typos, because using an undefined option
	will trigger an error.
*/
{
	protected static $values;
	protected static $defaults;
	protected $namespace;
	
	public function __construct($namespace, $defaults = [])
	{
		$this->namespace = preg_replace('/\s+/', '', $namespace);
		
		// Quit, if there are no default values to validate
		if (empty($defaults) || !is_array($defaults))
		{
			return;
		}
		
		$_defaults =& self::$defaults[$this->namespace];
		
		// Merge default option values
		if (!empty($_defaults))
		{
			$_defaults = array_merge($_defaults, $defaults);
		}
		else
		{
			$_defaults = $defaults;
		}
		
		$_values =& self::$values[$this->namespace];
		
		// Quit, if there are no values to validate
		if (empty($_values))
		{
			return;
		}
		
		$unknown = array_diff_key($_values, $_defaults);
		
		if (!empty($unknown))
		{
			trigger_error('Unexpected ' . $this->namespace . ' option'
				. (count($unknown) === 1 ? ': ' : 's: ')
				. implode(', ', array_keys($unknown)), E_USER_WARNING);
		}
	}
	
	public function get($option)
	/*
		Returns option value, if it has been previously set,
		or default value, if default values have been specified;
		otherwise triggers a warning.
	*/
	{
		$_defaults =& self::$defaults[$this->namespace];
		$_values =& self::$values[$this->namespace];
		
		if (is_array($_values) && array_key_exists($option, $_values))
		{
			return $_values[$option];
		}
		elseif (is_array($_defaults) && array_key_exists($option, $_defaults))
		{
			return $_defaults[$option];
		}
		
		trigger_error('Unexpected ' . $this->namespace . ' option: '
			. $option, E_USER_WARNING);
	}
	
	public function set($option, $value)
	/*
		Sets the option value.
	*/
	{
		$_defaults =& self::$defaults[$this->namespace];
		
		if (is_array($_defaults) && !array_key_exists($option, $_defaults))
		{
			trigger_error('Unexpected ' . $this->namespace . ' option: '
				. $option, E_USER_WARNING);
		}
		
		self::$values[$this->namespace][$option] = $value;
		
		return $this;
	}
}