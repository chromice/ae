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
	
	protected $method;
	protected $is_cli = false;
	protected $is_ajax = false;
	
	public function __construct()
	{
		// Who wants to deal with magic quotes?
		if (get_magic_quotes_gpc())
		{
		   trigger_error('Magic quotes must be turned off.', E_USER_ERROR);
		}
		
		// Method & type
		$this->is_cli = defined('STDIN');
		
		if (!$this->is_cli)
		{
			$this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'UKNOWN';
			$this->is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
			
			$this->segments = $this->_parse_uri();
		}
		else
		{
			$this->segments = $this->_parse_args();
		}
	}
	
	protected function _parse_uri()
	{
		// Parse uri
		$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
		$uri = '';
		
		// Determine the uri path
		foreach (array('PATH_INFO','REQUEST_URI','ORIG_PATH_INFO') as $var)
		{
			$uri = isset($_SERVER[$var]) ? $_SERVER[$var] : @getenv($var);

			if ($var === 'REQUEST_URI' && false !== ($pos = strpos($uri, '?')))
			{
				if (count($_GET) == 0)
				{
					// Query string has not been parsed. We do it ourselves.
					parse_str(substr($uri, $pos + 1), $_GET);
				}
				
				$uri = substr($uri, 0, $pos);
			}
			
			if ($var !== 'PATH_INFO')
			{
				$uri = str_replace($script_name, '', $uri);
			}

			if ($uri)
			{
				break;
			}
		}
		
		$segments = explode('/', trim($uri, '/'));
		
		return array_map('urldecode', $segments);
	}
	
	protected function _parse_args()
	{
		if (empty($_SERVER['argv']))
		{
			return array();
		}
		
		$segements = $_SERVER['argv'];
		array_shift($segements);
		
		return $segements;
	}
	
	public function is($what)
	{
		$result = true;
		$what = preg_split('/\s+/',$what);
		
		foreach ($what as $_what) switch ($_what)
		{
			case 'ajax':
			case 'remote':
				$result &= $this->is_ajax;
				break;
			case 'cli':
			case 'shell':
				$result &= $this->is_cli;
				break;
			case 'normal':
			case 'standard':
				$result &= !$this->is_cli && !$this->is_ajax;
				break;
			default:
				$result &= $this->method === strtoupper($_what);
		}
		
		return $result;
	}

	public function segment($offset, $default = false)
	{
		return isset($this->segments[$offset]) ? $this->segments[$offset] : $default;
	}
	
	public function segments($offset = 0, $length = 1)
	{
		if (is_null($length) || ($length + $offset) > count($this->segments))
		{
			$length = count($this->segments) - $offset;
		}
		
		return array_slice($this->segments, $offset, $length);
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
		$segments = $this->rewrite($segments);
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
				break;
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
{
	protected $path;
	protected $arguments;
	
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
	
	public function follow($parameters = array())
	{
		$parameters['route'] = $this;
		
		echo ae::render($this->path, $parameters);
	}
	
	public function argument($offset, $default = false)
	{
		return isset($this->arguments[$offset]) ? $this->arguments[$offset] : $default;
	}
}
