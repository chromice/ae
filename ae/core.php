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
	// ===================
	// = Code management =
	// ===================
	
	private static $utilities = array();
	private static $paths = array();
	private static $stack = array();
	
	public static function utilize($utility)
	/*
		Registers a utility by name and returns options object for that 
		utility. (See "ae/options.php" for more details.)
		
		All utilities must be located in "/utilities" directory. If there is 
		an 'index.php' script inside the utility directory, it will 
		be included.
	*/
	{
		$path = self::resolve('utilities/' . $utility, false);
		
		if (in_array($path, self::$utilities))
		{
			return;
		}
		
		if (!is_dir($path))
		{
			trigger_error('Cannot register "' . $path . '", because it is not a directory.', E_USER_ERROR);
		}
		
		self::$utilities[] = $path;
		
		// Initialise utility
		if (file_exists($path . '/index.php'))
		{
			require $path . '/index.php';
		}
		
		return ae::options($utility);
	}
	
	public static function resolve($path, $utilities = true)
	/*
		Resolves a relative path to a directory or file. By default
		the parent of 'ae' directory is used for base:
		
			echo ae::resolve('ae/options.php'); // '.../ae/options.php'
		
		You may register a utility to look in it as well:
		
			ae::utilize('utility-name');
			
			echo ae::resolve('bar.php'); // 'utilities/utility-name/bar.php'
		
		æ would fallback to the core directory, if it finds nothing in 
		registered utility directories:
		
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
		$try[] = $_path;
		
		if (true === $utilities)
		{
			$try[] = dirname(__DIR__) . '/' . $_path;
			
			foreach (self::$utilities as $utility)
			{
				$try[] = $utility . '/' . $_path;
			}
		}
		
		while ($__path = array_pop($try))
		{
			if (!file_exists($__path) || in_array($__path, self::$stack))
			{
				continue;
			}
			
			return realpath($__path);
		}
		
		throw new Exception('Could not resolve path: ' . $path);
	}
	
	public static function invoke($misc)
	/*
		Call this method from the loaded or imported file to define 
		class name or factory that load() method should use.
		
		If valid class name is passed, æ will create a new instance 
		of it every time the library is loaded:
		
			ae::invoke('LibraryClassName');
		
		Alternatively you can pass an callable function name, 
		callback or closure that sill be used as a factory:
			
			// Static callback
			ae::invoke(array('AnotherSingletonClassName', 'factory'));
			
			// Closure / singleton pattern
			ae::invoke(function($param, $param_2) {
				static $instance;
				
				if (!empty($instance)) 
				{
					return $instance;
				}
				
				return $instance = new SomeClass($param, $param_2;
			});


	*/
	{
		$data =& self::$paths[end(self::$stack)];
		
		if (is_callable($misc))
		{
			$data['callback'] = $misc;
		} 
		else
		{
			$data['class'] = $misc;
		}
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
	
	public static function load()
	/*
		Loads a script and attempts to invoke an object defined in it, 
		using invoke() method:
		
			$o = ae::load('ae/options.php', 'namespace');
			
		In this example, load() will return an instance of aeOptions.
		
		Please consult with the source code of the core libraries for 
		more real life examples.
	*/
	{
		$arguments = func_get_args();
		$path = array_shift($arguments);
		
		ae::import($path);
		
		$path = self::resolve($path);
		$data =& self::$paths[$path];
		
		if (isset($data['callback']))
		{
			$instance = call_user_func_array($data['callback'], $arguments);
		}
		else if (count($arguments) == 0)
		{
			$instance = new $data['class'];
		}
		else
		{
			$r = new ReflectionClass($data['class']);
			$instance = $r->newInstanceArgs($arguments);
		}
		
		return $instance;
	}
	
	public static function render($path, $parameters = null)
	/*
		Runs a script and returns the output as a string.
		
		You can pass some variables to the script via the second argument.
	*/
	{
		$ob = new aeBuffer();
		
		self::output($path, $parameters);
		
		return $ob->render();
	}
	
	public static function output($path, $parameters = null)
	/*
		Renders a script and echoes the output.
		
		You can pass some variables to the script via the second argument.
	*/
	{
		$path = self::resolve($path);
		$ps = new aeStack(self::$stack, $path);

		__ae_include__($path, $parameters);
	}
	
	public static function __callStatic($name, $arguments)
	/*
		Resolves all other static method calls as:
		
			ae::load('ae/{method}.php', ...arguments...);
	*/
	{
		array_unshift($arguments, 'ae/' . $name . '.php');
		
		return call_user_func_array(array('ae', 'load'), $arguments);
	}

	// ========
	// = HTML =
	// ========
	
	const html = 0;
	const text = 1;
	const tag = 2;
	const attribute = 3;
	const identifier = 4;
	
	public static function escape($value, $context = ae::text)
	/*
		Escape the string to be used in the selected utility:
		
		ae::html - don't escape;
		ae::text - escape all HTML code, but preserve entities;
		ae::tag - safe for tag or attribute names;
		ae::attribute - safe for attribute values;
		ae::identifier - alphnumerics, dashes and underscores only.
		
	*/
	{
		switch ($context) 
		{
			case ae::tag:
				return strtolower(preg_replace('/[^a-zA-Z0-9_:\-]/', '', $value));
			case ae::identifier:
				return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', $value));
			case ae::attribute:
			case ae::text:
				return preg_replace('/&amp;([a-z\d]+|#\d+|#x[a-f\d]+);/i', '&$1;', htmlspecialchars($value, ENT_QUOTES));
		}
		
		return $value;
	}
}

function __ae_include__($__ae_path__, $__ae_secret_array__ = null)
/*
	Runs an external script with nearly pristine environment, optionally 
	passing some parameters to it via the second argument.
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
	
		$buffer = new aeBuffer();
		
		echo 'Hello world!';
		
		echo $buffer->render();
		
	Buffered content may be used as a template:
	
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
	
	The buffer content is flushed, if not manually rendered.
*/
{
	protected $autoflush;
	protected $content;
	
	public function __construct($autoflush = true)
	{
		$this->autoflush = $autoflush;
		
		ob_start();
	}
	
	public function __destruct()
	{
		if (is_null($this->content))
		{
			$this->autoflush ? ob_end_flush() : ob_end_clean();
		}
	}
	
	public function reset()
	/*
		Use this method to reset an autoflushing buffer.
	*/
	{
		if (is_null($this->content))
		{
			$this->content = '';
			
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
	Provides an exception-safe way to push/pop values to/from stack.

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
	Provides an exception-safe way to swap the value of a variable 
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
