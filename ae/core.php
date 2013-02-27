<?php

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
		Registers a new context for the script that is being imported,
		loaded or rendered. You can specify your own directory path via
		second parameter.
		
		See __construct() and __destruct() for more details.
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
		Switches to a previously registered context.
		
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
			throw new Exception('Context "' . $context . '" has not been registered.');
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
		the parent of 'ae' directory is used for base:
		
			echo ae::resolve('ae/options.php'); // '.../ae/options.php'
		
		You may create a context for a different directory:
		
			ae::register('module.foo','path/to/foo');
			
			$context = new ae('module.foo');
			echo ae::resolve('bar.php'); // 'path/to/foo/bar.php'
		
		æ would fallback to the core directory, if it finds nothing in 
		the current context directory:
		
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
		Call this method at the top of the loaded or imported file 
		to define class name or factory that load() method should use.
		
			ae::invoke('LibraryClassName');
		
		æ will create a new instance of LibraryClassName every
		time the library is loaded.
			
			ae::invoke('SingletonClassName', ae::singleton);
		
		Only one instance of SingletonClassName will be created;
		all subsequent calls to ae::load() will return that instance.
			
			ae::invoke('a_factory_function', ae::factory);
		
		a_factory_function() function will be called.
			
			ae::invoke(
				array('AnotherSingletonClassName', 'factory'), 
				ae::factory | ae:singleton
			);
		
		AnotherSingletonClassName::factory() method will be 
		used to create and reuse a single instance of an object.
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
		Imports external script. Does not do much else.
		
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
		Loads a script and attempts to invoke an object defined in it, 
		using invoke() method:
		
			$o = ae::load('ae/options.php', 'namespace');
			
		In this example, load() will return an instance of aeOptions.
		
		Please consult with the source code of the core libraries for 
		more real life examples.
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
		Runs a script and returns the output as a string.
		
		You can pass some variables to the script via the second argument.
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
		Renders a script and echoes the output.
		
		You can pass some variables to the script via the second argument.
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
	
	public static function database($name = null)
	{
		return ae::load('ae/database.php', $name);
	}
	
	public static function file($path)
	{
		return ae::load('ae/file.php', $path);
	}
	
	public static function image($path)
	{
		return ae::load('ae/image.php', $path);
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
	Allows you to run an external script with nearly pristine namespace,
	optionally passing some parameters to it via second argument.
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
	
		$buffer = new aeBuffer();
		
		echo 'Hello world!';
		
		echo $buffer->render();
		
	Buffered content may be used as a simple template:
	
		$buffer = new aeBuffer();
		
		echo '<p><a href="{url}">{name}</a> has been viewed {visits} times.</p>';
		
		$buffer->output(array(
			'url' => $article->url,
			'name' => (
				strlen($article->name) > 20 
				? substr($article->name, 0, 19) . '&hellip;' 
				: $article->name
			),
			'visits' => number_format($article->visits)
		));
	
	If you don't call render() or output() methods, the captured content
	will be discarded:
	
		$buffer = new aeBuffer(); 
		
		echo 'Invisible text.';
		
		unset($buffer);
*/
{
	protected $content;
	
	public function __construct()
	{
		ob_start();
	}
	
	public function __destruct()
	{
		if (is_null($this->content))
		{
			ob_end_clean();
		}
	}
	
	public function render($variables = null)
	/*
		Returns the captured output as a string and stops capturing.
	*/
	{
		if (is_null($this->content))
		{
			$this->content = ob_get_clean();
		}
		
		if (is_array($variables) && !empty($variables))
		{
			$tokens = preg_replace('/.+/', '{$0}', array_keys($variables));
			
			return str_replace($tokens, $variables, $this->content);
		}
		
		return $this->content;
	}
	
	public function output($variables = null)
	/*
		Echoes the captured output and stops capturing.
	*/
	{
		echo $this->render($variables);
	}
}

class aeSwitch
/*
	Provides an exception–safe way to swap the value of a variable 
	for the lifetime of the switch object.
	
		$foo = 'foo';
		echo $foo; // echoes 'foo'
		
		$switch = new aeSwitch($foo, 'bar');
		
		echo $foo; // echoes 'bar'
		
		unset($switch);
		
		echo $foo; // echoes 'foo' again
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
