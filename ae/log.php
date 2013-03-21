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

aeLog::_setup();

class aeLog
/*
	Logs errors, notices, dumps, etc.,  outputs them in the response body 
	or X-ae-log header, or appends them to a log file.
	
	`log` options:
		`level`			- aeLog::everything or aeLog::problems (default).
		`environment`	- log env variables: true or false (default);
		`ip_whitelist`	- an array or comma-separated list of IP addresses;
		`directory`		- path to log directory.
		
*/
{
	const everything = 1;
	const problems = 2;
	
	protected static $log = array();
	protected static $problem = false;
	
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
		
		if ($class !== 'notice')
		{
			self::$problem = true;
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
	
	protected static function onShutdown()
	{
		$options = ae::options('log');
		
		if ($options->get('level', aeLog::problems) !== aeLog::everything && !self::$problem) 
		{
			return;
		}
		
		if ($path = $options->get('directory', false))
		{
			try 
			{
				$path = ae::resolve($path);
				
				if (is_dir($path) && is_writable($path)) 
				{
					$log = ae::file($path . '/log_' . gmdate('Y_m_d'));
					$log->open('a');
				}
				else
				{
					trigger_error('Log directory is not writable.', E_USER_ERROR);
				}
			} 
			catch (Exception $e)
			{
				trigger_error('Log directory does not exist.', E_USER_ERROR);
			}
		}
		
		$o = self::_ruler('=', 79) 
			. "Logged"
			. (isset($_SERVER['REQUEST_URI']) ? ' for ' . $_SERVER['REQUEST_URI'] : '')
			. ' at ' . gmdate('H:i:s', time()) . " GMT:"
			. self::_ruler('=', 79);
		
		if ($options->get('environment', false))
		{
			$o.= self::_environment();
		}
		
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
						$o.= self::_ruler();
					}
					
					if ($message['class'] === 'message')
					{
						$o.= "\n" . str_repeat(' ', 4) . str_replace(array("\n","\r"), ' ', $message['object']) . "\n";
					}
					else
					{
						$o.= self::_dump('(' . $message['type'] . ')', $message['object']);
					}
			}
		}
		
		$o.= self::_ruler() . "\n";
		
		if ($log)
		{
			$log->write($o);
		}
		
		// Is client's IP address in the whitelist?
		$ip = ae::request()->ip_address();
		$whitelist = $options->get('ip_whitelist', '127.0.0.1');
		
		if (is_string($whitelist))
		{
			$whitelist = preg_split('/,\s+?/', trim($whitelist));
		}
		
		// Choose presentation based on the request method
		$is_cli = defined('STDIN');
		$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
			&& strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
		
		if ($is_cli)
		{
			fwrite(STDERR, "$o\n");
		}
		else if (!in_array($ip, $whitelist))
		{
			return;
		}
		else if ($is_ajax && !headers_sent())
		{
			header('X-ae-log: ' . base64_encode($o));
		}
		else
		{
			echo "\n<!-- ae-log\n" . str_replace('-->', '- - >', $o) . "\n-->\n";
			
			if ($options->get('inspector', false))
			{
				echo '<script charset="utf-8">' . ae::render('inspector/inject.js') . '</script>';
			}
		}
	}
	
	protected static function _error_mask()
	{
		return E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
	}
	
	protected static function _warning_mask()
	{
		return E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING;
	}
	
	// ==================
	// = Log formatting =
	// ==================
	
	protected static function _environment()
	{
		$o = self::_ruler();
		
		if (!empty($_SERVER)) $o.= self::_dump('$_SERVER', $_SERVER);
		if (!empty($_GET)) $o.= self::_dump('$_GET', $_GET);
		if (!empty($_POST)) $o.= self::_dump('$_POST', $_POST);
		if (!empty($_COOKIE)) $o.= self::_dump('$_COOKIE', $_COOKIE);
		if (!empty($_SESSION)) $o.= self::_dump('$_SESSION', $_SESSION);
		
		return $o;
	}
	
	protected static function _ruler($char = '- ', $length = 40)
	{
		return "\n".str_repeat($char, $length)."\n";
	}
	
	protected static function _error($class, $error)
	{
		$o = self::_ruler() . "\n";
		
		$o.= ucfirst($class) . ': ' 
			. str_replace(array("\n","\r"), ' ', $error['message']) . "\n";
		
		if (isset($error['code']))
		{
			$o.= 'Code: ' . $error['code'] . "\n";
		}
		
		$o.= 'File: ' . $error['file'] . "\n";
		$o.= 'Line: ' . $error['line'] . "\n";
		
		if (!empty($error['context']))
		{
			$o.= "\nContext:\n";
			$o.= self::_dump(null, $error['context']);
		}
		
		if (!empty($error['backtrace']))
		{
			$o.= "\nBacktrace:\n\n";
			
			$offset = 0;
			$prefix = str_repeat(' ', 4);
			$length = count($error['backtrace']);
			
			while ($trace = array_shift($error['backtrace']))
			{
				$o.= $prefix . str_pad(($length - $offset++) . '.', 4, ' ', STR_PAD_RIGHT);
				
				if (!empty($trace['file']) && !empty($trace['line']) ) 
				{
					$o.= 'In "' . $trace['file'] . '" at line ' . $trace['line'] . ":\n\n";
					$o.= $prefix . $prefix;
				}
				
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
	
	protected static function _dump($name, $object, $level = 0)
	{
		$prefix_0 = str_repeat(' ', $level * 4);
		$prefix_1 = str_repeat(' ', ($level + 1) * 4);
		
		if ($object === false)
		{
			$object = 'FALSE';
		}
		else if ($object === true)
		{
			$object = 'TRUE';
		}
		else if ($object === null)
		{
			$object = 'NULL';
		}
		
		$o = is_null($name) ? "\n" : "\n" . $prefix_0 . '--- Dump: ' . $name . "\n\n";
		$o.= $prefix_1 . str_replace("\n", "\n" . $prefix_1, print_r($object, true));
		$o.= (is_scalar($object) ? "\n" : '') . (is_null($name) ? '' : "\n" . $prefix_0 . "--- End of dump\n");
		
		return $o;
	}

	// ============
	// = Handlers =
	// ============
	
	protected static $output;
	protected static $last_error;
	
	public static function _setup()
	{
		// We need to capture output until shutdown to be able to 
		// set `X-ae-log` header, when necessary.
		self::$output = new aeBuffer();
		
		// Set up all handlers
		set_error_handler(array('aeLog','_handleError'), E_ALL | E_STRICT);
		set_exception_handler(array('aeLog','_handleException'));
		register_shutdown_function(array('aeLog','_handleShutdown'));
	}
	
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
		
		self::$output->output();
	}
}