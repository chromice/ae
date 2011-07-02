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

ae::invoke('aeRequest', ae::singleton);

class aeRequest
/*
	Simple request abstration.
*/
{
	protected $segments = array();
	
	protected $rules = array();
	protected $rules_reverse = array();
	
	
	public function __construct($segments = null, $base_url = '/')
	{
		if (is_null($segments))
		{
			// TODO: Detect segments automatically.
		}
		
		$this->base_url = $base_url;
		$this->segments = explode('/', trim($segments, '/'));
		array_map('urldecode', $this->segments);
	}
	
	public function segment($offset, $default = false)
	{
		return isset($this->segments[$offset]) ? $this->segments[$offset] : $default;
	}
	
	public function segments($offset, $length = 1)
	{
		if (is_null($length) || ($length + $offset) > count($this->segments))
		{
			$length = count($this->segments) - $offset;
		}
		
		return array_slice($this->segments, $offset, $length);
	}
	
	public function is($type)
	{
		$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
		$is_cli = defined('STDIN');
		
		switch ($type)
		{
			case 'ajax':
			case 'remote':
				return $is_ajax;
			case 'cli':
			case 'shell':
				return $is_cli;
			case 'web':
			case 'normal':
				return !$is_cli && !$is_ajax;
			default:
				return false;
		}
	}

	public function route($base_dir, $aliases = array())
	{
		$arguments = null;
		$base_dir = ae::resolve($base_dir);
		
		if (!is_dir($base_dir))
		{
			trigger_error('Routing failed. Base directory "'.$base_dir.'" does not exist.', E_USER_ERROR);
		}
		
		if (is_array($aliases)) foreach ($aliases as $from => $to)
		{
			$from = trim($from, '/');
			$to = trim($to, '/');

			$_from = '/^' .preg_quote($from,'/') .'/';
			$_to = '/^' .preg_quote($to,'/') .'/';

			$this->rules[$_from] = $to;
			$this->rules_reverse[$_to] = $from;
		}
		
		$segments = implode('/', $this->segments);
		$segments = $this->_rewrite($segments);
		$segments = explode('/', $segments);
		
		for ($l = count($segments); $l > 0; $l--)
		{ 
			$path = array_slice($segments, 0, $l);
			$path = implode('/', $path);
			$path = empty($path) ? 'index.php' : $path . '.php';
			$path = $base_dir . '/' . $path;
			
			if (file_exists($path))
			{
				$arguments = array_slice($segments, $l);
			}
		} 
		
		if (is_null($arguments))
		{
			return new aeRoute(null, $segments);
		}
		else
		{
			return new aeRoute($path, $arguments);
		}
	}

	public function rewrite($uri, $reverse = false)
	/*
		Does the actual mapping.
	*/
	{
		$uri = trim($uri, '/');
		
		if (count($this->rules) === 0)
		{
			return $uri;
		}
		
		if (!$reverse)
		{
			return preg_replace(array_keys($this->rules), $this->rules, $uri);
		}
		else
		{
			return preg_replace(array_keys($this->rules_reverse), $this->rules_reverse, $uri);
		}
	}
}


class aeRoute
/*
	
*/
{
	public function __construct($path, $arguments)
	{
		$this->path = $path;
		$this->arguments = $arguments;
	}

	public function exists()
	{
		return !is_null($this->path);
	}
	
	public function path()
	{
		return $this->path;
	}
	
	public function argument($offset, $default = false)
	{
		return isset($this->arguments[$offset]) ? $this->arguments[$offset] : $default;
	}
}
