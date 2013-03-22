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
	
	private static $modules = array();
	private static $paths = array();
	
	public static function register($path)
	/*
		Registers a directory as a source of libraries.
	*/
	{
		$path = self::resolve($path, false);
		
		if (isset(self::$modules[$path]))
		{
			trigger_error('Cannot register "' . $path . '" again.', E_USER_ERROR);
		}
		
		self::$modules[$path] = true;
		self::$paths[] = $path;
	}
	
	public static function resolve($path, $all_contexts = true)
	/*
		Resolves a relative path to a directory or file. By default
		the parent of 'ae' directory is used for base:
		
			echo ae::resolve('ae/options.php'); // '.../ae/options.php'
		
		You may register a different directory to look in as well:
		
			ae::register('path/to/foo');
			
			echo ae::resolve('bar.php'); // 'path/to/foo/bar.php'
		
		æ would fallback to the core directory, if it finds nothing in 
		registered module directories:
		
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
		
		if (true === $all_contexts)
		{
			$try[] = dirname(__DIR__) . '/' . $_path;
			
			foreach (self::$paths as $module)
			{
				$try[] = $module . '/' . $_path;
			}
		}
		
		while ($__path = array_pop($try))
		{
			if (!file_exists($__path) && in_array($__path, $stack))
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
	
	private static $stack = array();
	
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
		$data =& self::$paths[end(self::$stack)];
		
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
		
		$ps = new aeStack(self::$stack, $path);
		
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
		
		$ps = new aeStack(self::$stack, $path);
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
	
	// ========
	// = HTML =
	// ========
	
	const html = 0;
	const text = 1;
	const tag = 2;
	const attribute = 3;
	
	public static function escape($value, $context = ae::text)
	/*
		Escape the string to be used in the selected module:
		
		ae::html - don't escape;
		ae::text - escape all HTML code, but preserve entities;
		ae::tag - safe for tag or attribute names;
		ae::attribute - safe for attribute values.
		
	*/
	{
		switch ($context) 
		{
			case ae::tag:
				return strtolower(preg_replace('/[^a-zA-Z0-9_:\-]/', '', $value));
			case ae::attribute:
			case ae::text:
				return preg_replace('/&amp;([a-z\d]+|#\d+|#x[a-f\d]+);/i', '&$1;', htmlspecialchars($value, ENT_QUOTES));
		}
		
		return $value;
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


class aeStack
/*
	Provides an exception–safe way to push/pop values to/from stack.

		$stack = array('foo');

		$item = new aeStack($stack, 'bar');

		var_dump($stack); // array('foo','bar');

		unset($item);

		var_dump($stack); // array('foo');
*/
{
	protected $stack;

	public function __construct(&$stack, $value)
	{
		$this->stack =& $stack;
		array_push($this->stack, $value);
	}

	public function __destruct()
	{
		array_pop($this->stack);
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
