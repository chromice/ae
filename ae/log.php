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
	protected $log = array();
	
	public function __construct()
	{
		// Set up all handlers
		set_error_handler(array($this,'_handleError'), E_ALL | E_STRICT);
		set_exception_handler(array($this,'_handleException'));
		register_shutdown_function(array($this,'_handleShutdown'));
	}

	public function log($messages)
	{
		$this->log[] = $messages;
	}

	protected function onError($error, $backtrace = null)
	{
		$error['backtrace'] = $backtrace;
		$this->log[] = $error;
	}

	protected function onShutdown()
	{
		// Present log.
		var_dump($this->log);
	}

	/*
		Event handling
	*/
	
	protected $last_error;

	public function _handleError($type, $message, $file, $line/*, $context*/)
	{
		
		$error['type'] = $type;
		$error['message'] = $message;
		$error['file'] = $file;
		$error['line'] = $line;
		// $error['context'] = $context;
		
		$this->last_error = $error;
		
		$trace = debug_backtrace();
		array_shift($trace);
		
		$this->onError($error, $trace);
	}

	public function _handleException($e)
	{
		$error['type'] = $e->getCode();
		$error['message'] = $e->getMessage();
		$error['file'] = $e->getFile();
		$error['line'] = $e->getLine();
		
		$this->onError($error, $e->getTrace());
	}

	public function _handleShutdown()
	{
		$error = error_get_last();
		$_error =& $this->last_error;
		
		if (is_array($error) && is_array($_error)
			&& $error['message'] === $_error['message']
			&& $error['file'] === $_error['file']
			&& $error['line'] === $_error['line'])
		{
			$this->onError($error);
		}
		
		$this->onShutdown();
	}
}