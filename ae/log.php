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

// TODO: convert back to static class and always import from core.php

ae::invoke('aeLog', ae::singleton);

class aeLog
/*
	Outputs a log of errors, notices, dumps, etc.
*/
{
	protected static $log = array();
	
	public static function log()
	{
		$messages = func_get_args();
		$length = count($messages);
		$i = 0;
		
		foreach ($messages as $message)
		{
			self::$log[] = array(
				'part' => ++$i,
				'length' => $length,
				'class' => is_string($message) ? 'message' : 'dump',
				'type' => gettype($message),
				'object' => $message
			);
		}
	}

	protected static function onError($error, $backtrace = null)
	{
		if ($error['type'] === 'exception')
		{
			$class = 'exception';
		}
		else if ($error['type'] & self::_error_mask())
		{
			$class = 'error';
		}
		else if ($error['type'] & self::_warning_mask())
		{
			$class = 'warning';
		}
		else
		{
			$class = 'notice';
		}
		
		// Notices do not have a backtrace
		if ($class !== 'notice' && !is_null($backtrace))
		{
			$error['backtrace'] = $backtrace;
		}
		
		unset($error['type']);
		
		self::$log[] = array(
			'class' => $class,
			'object' => $error
		);
	}
	
	protected static function _error_mask()
	{
		return E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
	}
	
	protected static function _warning_mask()
	{
		return E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING;
	}
	
	protected static function onShutdown()
	{
		$o = self::_header();
		
		while ($message = array_shift(self::$log))
		{
			switch ($message['class'])
			{
				case 'exception':
				case 'warning':
				case 'error':
				case 'notice':
					$o.= self::_error($message['class'], $message['object']);
					break;
				
				case 'dump':
				case 'message':
					if ($message['part'] === 1)
					{
						$o.= self::_horizontal_ruler();
					}
					
					if ($message['class'] === 'message')
					{
						$o.= "\n" . $message['object'] . "\n";
					}
					else
					{
						$o.= self::_dump('(' . $message['type'] . ')', $message['object']);
					}
			}
		}
		
		echo "<!--\n";
		echo $o;
		echo "\n-->";
	}
	
	// ==================
	// = Log formatting =
	// ==================
	
	public static function _header()
	{
		$o = self::_horizontal_ruler();
		
		if (!empty($_SERVER)) $o.= self::_dump('$_SERVER', $_SERVER);
		if (!empty($_GET)) $o.= self::_dump('$_GET', $_GET);
		if (!empty($_POST)) $o.= self::_dump('$_POST', $_POST);
		if (!empty($_COOKIE)) $o.= self::_dump('$_COOKIE', $_COOKIE);
		if (!empty($_SESSION)) $o.= self::_dump('$_SESSION', $_SESSION);
		
		return $o;
	}
	
	public static function _horizontal_ruler()
	{
		return "\n- - - - - - - -\n";
	}
	
	public static function _error($class, $error)
	{
		$o = self::_horizontal_ruler() . "\n";
		
		$o.= ucfirst($class) . ': ' . $error['message'] . "\n";
		
		if (isset($error['code']))
		{
			$o.= 'Code: ' . $error['code'] . "\n";
		}
		
		$o.= 'File: ' . $error['file'] . "\n";
		$o.= 'Line: ' . $error['line'] . "\n";
		
		if (!empty($error['context']))
		{
			$o.= "\nContext:\n";
			$o.= self::_dump('(array)', $error['context']);
		}
		
		if (!empty($error['backtrace']))
		{
			$o.= "\nBacktrace:\n\n";
			
			$offset = 0;
			$prefix = str_repeat(' ', 4);
			$length = count($error['backtrace']);
			
			while ($trace = array_shift($error['backtrace']))
			{
				$o.= $prefix . str_pad(($length - $offset++) . '.', 4, ' ', STR_PAD_RIGHT) .
					'In "' . $trace['file'] . '" at line ' . $trace['line'] . "\n\n";
				
				$o.= $prefix . $prefix;
				
				if (isset($trace['type']) && $trace['type'] === '->')
				{
					$o.= '$';
				}
				
				if (isset($trace['class']))
				{
					$o.= $trace['class'] . $trace['type'];
				}
				
				$o.= $trace['function'];
				
				$args = array();
				$dumps = array();
				$object_offset = 1;
				$array_offset = 1;
				
				if (isset($trace['object']))
				{
					$dumps[] = self::_dump('$' . $trace['class'], $trace['object'], 1);
				}
				
				if (isset($trace['args'])) while ($arg = array_shift($trace['args']))
				{
					if (is_array($arg) || is_object($arg))
					{
						$name = is_array($arg) ? 
							'$array_' . $array_offset++ : '$object_' . $object_offset++;
						$args[] = $name;
						$dumps[] = self::_dump($name, $arg, 1);
					}
					else if (is_numeric($arg))
					{
						$args[] = $arg;
					}
					else if ($arg === false)
					{
						$args[] = 'FALSE';
					}
					else if ($arg === true)
					{
						$args[] = 'TRUE';
					}
					else if ($arg === null)
					{
						$args[] = 'NULL';
					}
					else
					{
						$args[] = '"' . $arg . '"';
					}
				}
				
				$o.= '(' . implode(', ', $args) . ")\n" . implode('', $dumps) . "\n";
			}
		}
		
		
		return $o;
	}
	
	public static function _dump($name, $object, $level = 0)
	{
		$prefix_0 = str_repeat(' ', $level * 4);
		$prefix_1 = str_repeat(' ', ($level + 1) * 4);
		
		$o = "\n" . $prefix_0 . '--- Dump: ' . $name . "\n\n";
		$o.= $prefix_1 . str_replace("\n", "\n" . $prefix_1, print_r($object, true));
		$o.= (is_scalar($object) ? "\n" : '') . "\n" . $prefix_0 . "--- End of dump\n";
		
		return $o;
	}

	// ============
	// = Handlers =
	// ============
	
	protected static $last_error;
	
	public static function _handleError($type, $message, $file, $line, $context)
	{
		$error['type'] = $type;
		$error['context'] = $context;
		$error['message'] = $message;
		$error['file'] = $file;
		$error['line'] = $line;
		
		self::$last_error = $error;
		
		$trace = debug_backtrace();
		array_shift($trace);
		
		self::onError($error, $trace);
	}

	public static function _handleException($e)
	{
		$error['type'] = 'exception';
		$error['code'] = $e->getCode();
		$error['message'] = $e->getMessage();
		$error['file'] = $e->getFile();
		$error['line'] = $e->getLine();
		
		self::onError($error, $e->getTrace());
	}

	public static function _handleShutdown()
	{
		$error = error_get_last();
		$_error =& self::$last_error;
		
		if (is_array($error) && is_array($_error)
		&& ($error['message'] !== $_error['message']
			|| $error['file'] !== $_error['file']
			|| $error['line'] !== $_error['line']))
		{
			self::onError($error);
		}
		
		self::onShutdown();
	}
}

// Set up all handlers
set_error_handler(array('aeLog','_handleError'), E_ALL | E_STRICT);
set_exception_handler(array('aeLog','_handleException'));
register_shutdown_function(array('aeLog','_handleShutdown'));