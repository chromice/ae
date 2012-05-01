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
{
	protected static $log = array();
	
	public static function log()
	{
		$messages = func_get_args();
		$length = count($messages);
		$i = 0;
		
		if ($length > 0) foreach ($messages as $message)
		{
			self::$log[] = array(
				'part' => ++$i,
				'length' => $length,
				'class' => is_array($message) || is_object($message) ? 'dump' : 'message',
				'type' => gettype($message),
				'object' => $message);
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
		
		// Notices do not backtrace
		if ($class !== 'notice')
		{
			$error['backtrace'] = $backtrace;
		}
		
		self::$log[] = array(
			'class' => $class,
			'object' => $error
		);
	}

	protected static function onShutdown()
	{
		$o = "<!--\n";
		$message_offset = null;
		
		while ($message = array_shift(self::$log))
		{
			$class = $message['class'];
			
			// Header, e.g. "----- Exception -----"
			$header = ' ' . ucfirst($message['class']) . ' ';
			$header = "\n" . str_pad($header, 21, "-", STR_PAD_BOTH) . "\n";
			
			switch ($class)
			{
				case 'exception':
				case 'warning':
				case 'error':
				case 'notice':
					$error = $message['object'];
					
					$o.= $header;
					$o.= 'In "' . $error['file'] . '" at line ' . $error['line'] . ":\n";
					$o.= $error['message'] . "\n";
					
					if (!empty($error['context']))
					{
						# code...
					}
					
					if (!empty($error['backtrace']));
					{
						# code...
					}
					
					break;
				case 'dump':
				case 'message':
					if ($message['part'] === 1)
					{
						// Print normal header
						$o.= $header;
						
					}
					else
					{
						// Print short header and pad output
						$o.= "\n    " . substr($header, 5);
					}
					
					$o.= $message['object'];
					break;
			}
		}
		
		$o.= "\n-->";
		
		// present output
		echo $o;
	}

	protected function _error_mask()
	{
		return E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR;
	}
	
	protected function _warning_mask()
	{
		return E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING;
	}

	/*
		Event handling
	*/
	
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
			&& $error['message'] === $_error['message']
			&& $error['file'] === $_error['file']
			&& $error['line'] === $_error['line'])
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