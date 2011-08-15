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

ae::invoke(array('aeRoute','request'), ae::factory);

class aeRequest
/*
	Simple request abstration.
*/
{
	protected $segments = array();
	
	public function __construct($segments)
	{
		$this->segments = is_array($segments) ? $segments : explode('/', trim($segments, '/'));
	}
		
	public function is($what)
	{
		$is_cli = defined('STDIN');
		$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
			&& strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
		
		$method = isset($_SERVER['REQUEST_METHOD']) ? 
			strtoupper($_SERVER['REQUEST_METHOD']) : 'UKNOWN';
		
		$result = true;
		$what = preg_split('/\s+/',$what);
		
		foreach ($what as $_what) switch ($_what)
		{
			case 'ajax':
			case 'remote':
				$result &= $is_ajax;
				break;
			case 'cli':
			case 'shell':
				$result &= $is_cli;
				break;
			case 'normal':
			case 'standard':
				$result &= !$is_cli && !$is_ajax;
				break;
			default:
				$result &= $method === strtoupper($_what);
		}
		
		return $result;
	}
	
	public function uri($offset = 0, $length = null)
	{
		if (is_null($length) || ($length + $offset) > count($this->segments))
		{
			$length = count($this->segments) - $offset;
		}
		
		return implode('/', array_slice($this->segments, $offset, $length));
	}
	
	public function segment($offset, $default = false)
	{
		return isset($this->segments[$offset]) ? $this->segments[$offset] : $default;
	}
	
	public function route($base)
	{
		return new aeRoute($this->uri(), $base);
	}
}


class aeRoute
{
	protected static $depth = 0;
	protected static $segments;
	
	public static function request($segments)
	{
		// Who wants to deal with magic quotes?
		if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
		{
		   trigger_error('Magic quotes must be turned off!', E_USER_ERROR);
		}
		
		// Custom request
		if (!is_null($segments))
		{
			return new aeRequest($segments);
		}
		
		// Obtain segments
		if (empty(self::$segments))
		{
			self::$depth = 0;
			self::$segments = defined('STDIN') ? self::_parse_args() : self::_parse_uri();
		}
		
		return new aeRequest(array_slice(self::$segments, self::$depth));
	}
	
	protected static function _parse_uri()
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
	
	protected static function _parse_args()
	{
		if (empty($_SERVER['argv']))
		{
			return array();
		}
		
		$segments = $_SERVER['argv'];
		array_shift($segments);
		
		return $segments;
	}
	
	protected $uri;
	protected $offset;
	protected $path;
	
	public function __construct($uri, $base)
	{
		$base = ae::resolve($base);
		
		if (!file_exists($base))
		{
			trigger_error('Routing failed. "'.$base.'" does not exist.', E_USER_ERROR);
		}
		
		if (is_file($base))
		{
			$this->path = $base;
			$this->offset = 0;
			
			return;
		}

		$uri = trim($uri, '/');
		$segments = explode('/', $uri);
		
		for ($l = count($segments); $l > 0; $l--)
		{ 
			$path = array_slice($segments, 0, $l);
			$path = implode('/', $path);
			$path = empty($path) ? 'index.php' : $path . '.php';
			$path = $base . '/' . $path;
		
			if (file_exists($path))
			{
				$this->path = $path;
				$this->offset = $l;
				break;
			}
		} 
	}
		
	public function exists()
	{
		return !is_null($this->path);
	}
	
	public function follow($parameters = array())
	{
		if (is_null($this->path))
		{
			trigger_error('Route does not exist.', E_USER_ERROR);
		}
		
		$depth = self::$depth + $this->offset;
		$ds = new aeSwitch(self::$depth, $depth);
		
		echo ae::render($this->path, $parameters);
	}
	
}
