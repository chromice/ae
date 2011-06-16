<?php

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


final class ae
/*
	Holds everything together.
*/
{
	// =====================
	// = Context switching =
	// =====================
	
	private $previous_context;
	private static $current_context;
	private static $contexts = array();
	private static $paths = array();
	
	public $path;
	
	public function __construct($context, $path = null)
	/*
		Creates a new context that all ae::*() calls will use while the object exists.
	
			$context = new ae('modules.auth');
			$model = ae::load('data.php'); // load data model for auth module
	*/
	{
		if (!isset(self::$contexts[$context]))
		{
			if (!is_null($path))
			{
				$this->path = self::resolve($path);
			}
			else if (!empty(self::$current_path))
			{
				$this->path = dirname(self::$current_path) . '/';
			}
			else
			{
				throw new Exception('Root path for context "'.$context.'" could not be detected.');
			}

			self::$contexts[$context] = $this->path;
		}
		else
		{
			$this->path = self::$contexts[$context];
		}
		
		$this->previous_context = self::$current_context;
		self::$current_context = $context;
	}
	
	public function __destruct()
	/*
		Restores previous context.
	*/
	{
		self::$current_context = $this->previous_context;
	}
	
	public static function resolve($path)
	/*
		Resolves relative paths to directories and files.
	*/
	{
		if (isset(self::$paths[$path]))
		{
			return $path;
		}
		
		$_path = str_replace('\\','/', $path);
		$_path = trim($_path, '/');
		
		$try[] = $path;
		$try[] = dirname(__FILE__) . '/' . $_path;
		
		if (!is_null(self::$current_context) 
			&& isset(self::$contexts[self::$current_context]))
		{
			$try[] = self::$contexts[self::$current_context] . '/' . $_path;
		}
		
		while ($__path = array_pop($try))
		{
			if (!file_exists($__path) || self::$current_path == $__path)
			{
				continue;
			}
			
			return realpath($__path);
		}
		
		return $_path;
	}
	
	// ===================
	// = Code management =
	// ===================
	
	const factory = 1;
	const singleton = 2;
	
	private static $current_path;
	
	public static function invoke($misc, $type = 0)
	/*
		Defines a class to instantiate or an object factory to use when the file is loaded.
		
			ae::invoke('SomeClassName'); 
			ae::invoke('SomeClassName', ae::singleton);
			ae::invoke('some_function_name', ae::factory);
			ae::invoke(array('SingletonClass','instance'), ae::factory | ae::singleton);
	*/
	{
		$data =& self::$paths[self::$current_path];
		
		if (ae::factory & $type)
		{
			$data['callback'] = $misc;
		} 
		else 
		{
			$data['class'] = $misc;
		}
		
		$data['singleton'] = (bool)(ae::singleton & $type);
	}
	
	public static function import($path)
	/*
		Resolves relative path and imports the file.
		
			ae::import('path/to/script.php');
	*/
	{
		$path = self::resolve($path);
		
		if (isset(self::$paths[$path]))
		{
			return;
		}
		
		$ps = new aeSwitch(self::$current_path, $path);
		
		try {
			self::$paths[$path] = null;
			self::_include();
		} catch (Exception $e) {
			throw $e;
		}
		
		// TODO: return some meta information, e.g. class name, callback, type, etc
	}
	
	public static function load($path, $parameters = null)
	/*
		Imports the file and attepmts to invoke an object.
		
			$image = ae::load('image.php','/path/to/image.png');
	*/
	{
		$path = self::resolve($path);
		
		if (isset(self::$paths[$path]))
		{
			return self::_invoke($path, $parameters);
		}
		
		$ps = new aeSwitch(self::$current_path, $path);
		
		try {
			self::$paths[$path] = null;
			self::_include();
		} catch (Exception $e) {
			throw $e;
		}
		
		return self::_invoke($path, $parameters);
	}
	
	public static function render($path, $parameters = null)
	/*
		Renders a view and returns it as a string.
	*/
	{
		$path = self::resolve($path);
		
		$ps = new aeSwitch(self::$current_path, $path);
		$ob = new aeBuffer();
		
		try {
			self::_include($parameters);
		} catch (Exception $e) {
			throw $e;
		}
		
		$output = $ob->output();
		
		return $output;
	}
	
	private static function _include($__ae_secret_array__ = null)
	/*
		Includes an external script with nearly pristine namespace
	*/
	{
		if (is_array($__ae_secret_array__))
		{
			extract($__ae_secret_array__, EXTR_REFS);
		}
		
		unset($__ae_secret_array__);
		
		require self::$current_path;
	}
	
	private static function _invoke($path, $parameters)
	/*
		Invokes an object for the path.
	*/
	{
		if (!isset(self::$paths[$path]))
		{
			return;
		}
		
		$data =& self::$paths[$path];
		
		if (isset($data['instance']))
		{
			return $data['instance'];
		}
		
		if (isset($data['callback']))
		{
			$instance = call_user_func($data['callback'], $parameters);
		}
		else
		{
			$instance = new $data['class']($parameters);
		}
		
		if (!empty($data['singleton']) && $instance)
		{
			$data['instance'] = $instance;
		}
		
		return $instance;
	}
}

class aeBuffer
/*
	PHP output buffer abstraction layer. 
	
	It ensures that the scope output is always captured or discarded.
*/
{
	protected $level;
	
	public function __construct()
	{
		$this->level = ob_get_level();
		ob_start();
	}
	
	public function __destruct()
	{
		if (is_null($this->level))
		{
			return;
		}
		
		while ($this->level < ob_get_level())
		{
			ob_end_clean();
		}
		
		$this->level = null;
	}
	
	public function output()
	{
		if (is_null($this->level) || $this->level >= ob_get_level())
		{
			return ''; 
		}
		
		while ($this->level < ob_get_level() - 1)
		{
			ob_end_flush();
		}
		
		$this->level = null;
		
		return ob_get_clean();
	}
}

class aeSwitch
/*
	Switches the value of a variable for the current context.
*/
{
	protected $var;
	protected $previous;
	
	public function __construct(&$var, $value)
	{
		$this->previous = $var;
		$this->var =& $var;
		$this->var = $value;
	}
	
	public function __destruct()
	{
		$this->var = $this->previous;
	}
}

