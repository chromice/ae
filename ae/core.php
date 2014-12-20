<?php

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

namespace ae;

final class Core
/*
	This is a godly essence that holds everything together.
	
	- It lets you register directories as contexts, modules or apps.
	- It helps you resolve relative paths against registered directories.
	- It manages loading (and overloading) libraries for you.
	- It exposes an interface to get/flush script output.
	- It lets you escape strings for various parts of HTML.
*/
{
	// ======================
	// = Context management =
	// ======================
	
	const application = true;
	const module = null;
	const context = false;
	
	public static function register($path, $type = Core::module)
	/*
		Registers a directory as a valid module or context, or launches
		an application.
			
			// Register a new context:
			Core::register('path/to/directory', Core::context);

			// Register a new context and run /index.php, if it exists.
			Core::register('path/to/module'); // or
			Core::register('path/to/module', Core::module);
			
			// Register context and run /index.php or trigger error.
			Core::register('path/to/module', Core::application);
		
		See `Core::resolve()` for more information.
		
		Throws `CoreException`, if path cannot be resolved.
	*/
	{
		$path = self::resolve($path, true);
		
		if (in_array($path, self::$contexts))
		{
			return;
		}
		
		if (!is_dir($path))
		{
			trigger_error('Cannot register "' . $path . '", because it is not a directory.', E_USER_ERROR);
		}
		
		// Application's context must always be at the top
		if ($type === Core::application)
		{
			array_unshift(self::$contexts, $path);
		}
		else
		{
			array_push(self::$contexts, $path);
		}
		
		if ($type !== Core::context && file_exists($path . '/index.php'))
		{
			__ae_include__($path . '/index.php');
		}
		elseif ($type === Core::application)
		{
			trigger_error('Cannot launch "' . $path . '/index.php". File does not exist.', E_USER_ERROR);
		}
	}
	
	// ===================
	// = Code management =
	// ===================
	
	private static $contexts = array();
	private static $paths = array();
	private static $stack = array();
	
	public static function resolve($path, $search = true)
	/*
		Resolves a relative path to a directory or file. By default
		the parent of 'ae' directory is used for base:
		
			echo Core::resolve('ae/options.php'); // '.../ae/options.php'
		
		You may register a directory to look in it as well:
		
			Core::register('some/directory', Core::context);
			
			echo Core::resolve('bar.php'); // 'some/directory/bar.php'
		
		æ would fall back to the core directory, if it finds nothing in 
		registered directories:
		
			$request = Core::load('ae/request.php');
	
		Throws `CoreException`, if path cannot be resolved.
	*/
	{
		if (empty($path))
		{
			trigger_error('Empty path cannot be resolved.', E_USER_ERROR);
			
			return;
		}
		elseif (isset(self::$paths[$path]))
		{
			return $path;
		}
		
		if ($path === '/')
		{
			return dirname(__DIR__);
		}
		
		$_path = str_replace('\\','/', $path);
		$_path = trim($_path, '/');
		
		$try[] = $path;
		$try[] = $_path;
		
		if (true === $search)
		{
			$try[] = dirname(__DIR__) . '/' . $_path;
			
			foreach (self::$contexts as $context)
			{
				$try[] = $context . '/' . $_path;
			}
		}
		
		while ($__path = array_pop($try))
		{
			if ($__path === end(self::$stack) || !file_exists($__path))
			{
				continue;
			}
			
			return realpath($__path);
		}
		
		throw new CoreException('Could not resolve path: ' . $path);
	}
	
	public static function import($path)
	/*
		Imports external script. Does not do much else.
		
			Core::import('path/to/script.php');
		
		Throws `CoreException`, if path cannot be resolved.
	*/
	{
		$path = self::resolve($path);
		
		if (isset(self::$paths[$path]))
		{
			return;
		}
		
		if (is_dir($path))
		{
			trigger_error('Cannot import "' . $path . '", because it is a directory.', E_USER_ERROR);
		}
		
		$ps = new ValueStack(self::$stack, $path);
		
		self::$paths[$path] = array();
		
		__ae_include__($path);
	}
	
	public static function invoke($misc)
	/*
		Call this method from the loaded or imported file to define 
		class name or factory that `Core::load()` method should use.
		
		If valid class name is passed, æ will create a new instance 
		of it every time the library is loaded:
		
			Core::invoke('LibraryClassName');
		
		Alternatively you can pass an callable function name, 
		callback or closure that sill be used as a factory:
			
			// Static callback
			Core::invoke(array('AnotherSingletonClassName', 'factory'));
			
			// Closure / singleton pattern
			Core::invoke(function ($param, $param_2) {
				static $instance;
				
				if (!empty($instance)) 
				{
					return $instance;
				}
				
				return $instance = new SomeClass($param, $param_2);
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
	
	public static function load()
	/*
		Loads a script and attempts to invoke an object defined in it, 
		using `Core::invoke()` method:
		
			$o = Core::load('ae/options.php', 'namespace');
		
		In this example, `Core::load()` will return an instance of `Options`.
		
		Please consult with the source code of the core libraries for 
		more real life examples.
	
		Throws `CoreException`, if path cannot be resolved.
	*/
	{
		$arguments = func_get_args();
		$path = array_shift($arguments);
		
		Core::import($path);
		
		$path = self::resolve($path);
		$data =& self::$paths[$path];
		
		if (isset($data['callback']))
		{
			$instance = call_user_func_array($data['callback'], $arguments);
		}
		elseif (count($arguments) == 0)
		{
			$instance = new $data['class'];
		}
		else
		{
			$r = new \ReflectionClass($data['class']);
			$instance = $r->newInstanceArgs($arguments);
		}
		
		return $instance;
	}
	
	public static function __callStatic($name, $arguments)
	/*
		Resolves all other static method calls as:
		
			Core::load('ae/{method}.php', ...arguments...);
		
		I.e. the following statement evaluates to TRUE:
		
			Core::request() === Core::load('ae/request.php');
			
	*/
	{
		array_unshift($arguments, 'ae/' . $name . '.php');
		
		try
		{
			return call_user_func_array(array('\ae\Core', 'load'), $arguments);
		}
		catch (CoreException $e) 
		{
			if (in_array($name, array('log', 'probe')))
			{
				return new Stud();
			}
			
			throw $e;
		}
	}
	
	
	// ====================
	// = Script rendering =
	// ====================
	
	public static function render($path, $parameters = null)
	/*
		Runs a script and returns the output as a string.
		
		You can pass some variables to the script via the second argument.
		
		Throws `CoreException`, if path cannot be resolved.
	*/
	{
		$ob = new Buffer();
		
		self::output($path, $parameters);
		
		return $ob->render();
	}
	
	public static function output($path, $parameters = null)
	/*
		Renders a script and echoes the output.
		
		You can pass some variables to the script via the second argument.
		
		Throws `CoreException`, if path cannot be resolved.
	*/
	{
		$path = self::resolve($path);
		$ps = new ValueStack(self::$stack, $path);
		
		__ae_include__($path, $parameters);
	}


	// ========
	// = HTML =
	// ========
	
	const html = 0;
	const text = 1;
	const name = 2;
	const value = 3;
	const identifier = 4;
	
	public static function escape($value, $context = Core::text)
	/*
		Escape the string to be used in the chosen context:
		
		`Core::html` - don't escape;
		`Core::text` - escape all HTML code, but preserve entities;
		`Core::name` - safe for tag or attribute names;
		`Core::value` - safe for attribute values;
		`Core::identifier` - alphnumerics, dashes and underscores only.
		
	*/
	{
		switch ($context) 
		{
			case Core::name:
				return preg_replace('/[^a-zA-Z0-9_:\-]/', '', $value);
			case Core::identifier:
				return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value);
			case Core::value:
			case Core::text:
				return preg_replace('/&amp;([a-zA-Z\d]+|#\d+|#[xX][a-fA-F\d]+);/', '&$1;', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
		}
		
		return $value;
	}
}

class CoreException extends \Exception {}


// =================================
// = Utility classes and functions =
// =================================

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

class Stud
/*
	A simple stud object that returns a stud object for any method call.
	
		$stud = new Stud();
		
		$stud->can('pretend')->to('be')->any('other', 'library');
*/
{
	public function __call($name, $arguments)
	{
		return $this;
	}
	
	public static function __callStatic($name, $arguments)
	{
		return new Stud();
	}
}


class Trap
/*
	PHP output buffer abstraction layer. 
	
		$buffer = new Trap();
		
		echo 'Hello world!';
		
		echo $buffer->render();
	
	The trapped content has to be flushed manually.
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
		
		Trapped content may be used as a template:
		
			$content = new Trap();
			
			echo '<p><a href="{url}">{name}</a> has been viewed {visits} times.</p>';
			
			$output = $content->render(array(
				'url' => $article->url,
				'name' => (
					strlen($article->name) > 20 
					? substr($article->name, 0, 19) . '&hellip;' 
					: $article->name
				),
				'visits' => number_format($article->visits)
			));
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

class Buffer extends Trap
/*
	PHP output buffer abstraction layer. 
	
		$buffer = new Buffer();
		
		echo 'Hello world!';
		
		echo $buffer->render();
	
	The buffered content is flushed, if not caught.
*/
{
	public function __destruct()
	{
		if (is_null($this->content))
		{
			ob_end_flush();
		}
	}
	
	public function reset()
	/*
		Resets an autoflushing buffer.
	*/
	{
		if (is_null($this->content))
		{
			$this->content = '';
			
			ob_end_clean();
		}
	}
}

class ValueStack
/*
	Provides an exception-safe way to push/pop values to/from stack.

		$stack = array('foo');
		
		$item = new ValueStack($stack, 'bar');
		
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

class ValueSwitch
/*
	Provides an exception-safe way to swap the value of a variable 
	for the lifetime of the switch object.
	
		$foo = 'foo';
		echo $foo; // echoes 'foo'
		
		$switch = new ValueSwitch($foo, 'bar');
		
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

