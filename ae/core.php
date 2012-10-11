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
	
	public static function register($context, $path = null)
	/*
		Registers a new context for the script that is being imported, loaded 
		or rendered. You can specify your own directory path via second parameter.
		
		See ae::__construct() for more details.
	*/
	{
		if (isset(self::$contexts[$context]))
		{
			throw new Exception('Cannot re-register context "' . $context . '".');
		}
		
		if (!is_null($path))
		{
			$path = self::resolve($path);
		}
		else if (!empty(self::$current_path))
		{
			$path = dirname(self::$current_path) . '/';
		}
		else
		{
			throw new Exception('Automatic path detection failed for context "' . $context . '".');
		}
		
		self::$contexts[$context] = $path;
	}
	
	public function __construct($context)
	/*
		Switches to a previously registered context. Paths will be attempted
		
			// First, register the context:
			ae::register('module.examples','examples/');
			
			// Then, when you need it, use it. (Ideally it should be 
			// created in the scope of a function and destroyed as soon
			// as possible.)
			$context1 = new ae('module.examples');
			echo ae::resolve('foo.php'); // 'examples/foo.php'
		
			// You can nest contexts, as long as you destroy them correctly 
			// in the correct (LIFO) order.
			$context2 = new ae('module.samples', 'samples/')
			
			echo ae::resolve('foo.php'); // 'samples/foo.php'
			
			// Unsetting the second context pops up the first one.
			// NB! Mind the order!
			unset($context2);
			
			// This resolves in the right context
			echo ae::resolve('foo.php'); // 'examples/foo.php'
			
			// Reverting back to original
			unset($context1);
			
			echo ae::resolve('foo.php'); 
			// e.g. 'original/context/path/foo.php'
	*/
	{
		if (!isset(self::$contexts[$context]))
		{
			throw new Exception('Context "'.$context.'" has not been registered.');
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
		Resolves a relative path to a directory or file. By default
		'ae' directory is used for base:
		
			echo ae::resolve('options.php'); // '.../ae/options.php'
		
		You may create a context for a different directory:
		
			$context = new ae('module.foo','path/to/foo');
			echo ae::resolve('bar.php'); // 'path/to/foo/bar.php'
		
		ae would still fallback to the core directory:
		
			$request = ae::load('ae/request.php');
		
	*/
	{
		if (empty($path))
		{
			trigger_error('Empty path cannot be resolved.', E_USER_ERROR);
			
			return;
		}
		else if (isset(self::$paths[$path]))
		{
			return $path;
		}
		
		$_path = str_replace('\\','/', $path);
		$_path = trim($_path, '/');
		
		$try[] = $path;
		$try[] = dirname(__DIR__) . '/' . $_path;
		
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
		
		throw new Exception('Could not resolve path: ' . $path);
	}
	
	// ===================
	// = Code management =
	// ===================
	
	const factory = 1;
	const singleton = 2;
	
	private static $current_path;
	
	public static function invoke($misc, $type = 0)
	/*
		Called from inside of the loaded or imported script to define 
		class name or factory that load() method should use.
		
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
		Imports external script. Does not do anything else.
		
			ae::import('path/to/script.php');
		
	*/
	{
		$path = self::resolve($path);
		
		if (isset(self::$paths[$path]))
		{
			return;
		}
		
		$ps = new aeSwitch(self::$current_path, $path);
		
		self::$paths[$path] = array();
		
		__ae_include__($path);
	}
	
	public static function load($path, $parameters = null)
	/*
		Loads a script and attempts to invoke an object defined in it:
		
			$image = ae::load('ae/image.php','/path/to/image.png');
		
	*/
	{
		ae::import($path);
		
		$path = self::resolve($path);
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
	
	public static function render($path, $parameters = null)
	/*
		Renders a script and its output.
	*/
	{
		$path = self::resolve($path);
		
		$ps = new aeSwitch(self::$current_path, $path);
		$ob = new aeBuffer();
		
		__ae_include__($path, $parameters);
		
		return $ob->render();
	}
	
	public static function output($path, $parameters = null)
	/*
		Renders a script and echoes its output.
	*/
	{
		echo self::render($path, $parameters);
	}
	
	// =================================
	// = Shortcuts for stock libraries =
	// =================================
	
	public static function container($path)
	{
		return ae::load('ae/container.php', $path);
	}
	
	public static function database($name)
	{
		return ae::load('ae/database.php', $name);
	}
	
	public static function file($path)
	{
		return ae::load('ae/file.php', $path);
	}
	
	public static function log()
	{
		if (class_exists('aeLog'))
		{
			call_user_func_array(array('aeLog', 'log'), func_get_args());
		}
	}
	
	public static function options($namespace = null)
	{
		return ae::load('ae/options.php', $namespace);
	}
	
	public static function probe($name)
	{
		return ae::load('ae/probe.php', $name);
	}
	
	public static function request($segments = null)
	{
		return ae::load('ae/request.php', $segments);
	}

	public static function response($type = null)
	{
		return ae::load('ae/response.php', $type);
	}
}

function __ae_include__($__ae_path__, $__ae_secret_array__ = null)
/*
	Includes an external script with nearly pristine namespace
*/
{
	if (is_array($__ae_secret_array__))
	{
		extract($__ae_secret_array__, EXTR_REFS);
	}
	
	unset($__ae_secret_array__);
	
	require $__ae_path__;
}


class aeBuffer
/*
	PHP output buffer abstraction layer. 
	
	It ensures that the scope output is always captured or discarded 
	correctly, even when an exception is thrown.
*/
{
	protected $level;
	protected $content;
	
	public function __construct()
	{
		ob_start();
		$this->level = ob_get_level();
	}
	
	public function __destruct()
	{
		while ($this->level < ob_get_level() - 1)
		{
			ob_end_clean();
		}
	}
	
	public function render($variables = null)
	{
		if (!is_null($this->content))
		{
			$content = $this->content;
		}
		else if ($this->level != ob_get_level())
		{
			trigger_error('Unexpected buffer level!', E_USER_ERROR);
			$content = '';
		}
		else
		{
			$content = $this->content = ob_get_clean();
		}
		
		if (is_array($variables) && !empty($variables))
		{
			$tokens = preg_replace('/.+/', '{$0}', array_keys($variables));
			$content = str_replace($tokens, $variables, $content);
		}
		
		return $content;
	}
	
	public function output($variables = null)
	{
		echo $this->render($variables);
	}
}

class aeSwitch
/*
	Provides an exception–safe way to swap the value of a variable 
	for the lifetime of the switch object.
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
