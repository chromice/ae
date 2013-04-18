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

ae::invoke(array('aeRoute','request'), ae::factory);

class aeRequest
/*
	Request abstration.
	
	`request` options:
		`proxies`	-	an array or comma-separated list of IP addresses.
*/
{
	const is_cli = __ae_request_cli__;
	const is_ajax = __ae_request_ajax__;
	
	const protocol = __ae_request_protocol__;
	const method = __ae_request_method__;
	
	protected $depth = 0;
	protected $segments = array();
	
	public function __construct($depth, $segments)
	{
		$this->depth = $depth;
		$this->segments = is_array($segments) ? $segments : explode('/', trim($segments, '/'));
	}
	
	public function is_routed()
	/*
		Returns TRUE if requested was routed.
	*/
	{
		return $this->depth > 0;
	}
	
	public function base()
	/*
		Returns the base URI for the redirected request.
	*/
	{
		return implode('/', array_slice($this->segments, 0, $this->depth));
	}
	
	public function segment($offset, $default = false)
	/*
		Returns segment or default value, if segment does not exist.
	*/
	{
		$offset += $this->depth;
		
		return isset($this->segments[$offset]) ? $this->segments[$offset] : $default;
	}
	
	public function route($base)
	/*
		Returns an instance of aeRoute for base path and current uri.
	*/
	{
		$uri = implode('/', array_slice($this->segments, $this->depth));
		
		return new aeRoute($uri, $base);
	}
	
	// =======================
	// = General information =
	// =======================
	
	protected static $_uri;
	protected static $_type;
	protected static $_segments;
	
	public static function uri()
	/*
		Returns URI as a string.
	*/
	{
		self::_parse_request();
		
		return self::$_uri;
	}
	
	public static function type()
	/*
		Returns the type of the requested resource.
	*/
	{
		self::_parse_request();
		
		return self::$_type;
	}
	
	public static function segments()
	/*
		Returns an array of URI segments.
	*/
	{
		self::_parse_request();
		
		return self::$_segments;
	}
	
	public static function ip_address()
	/*
		Returns IP address of the client. If the server is behind a proxy or
		load balancer, this method will return its IP address, unless it has been
		added to the list of known `proxies`.
	*/
	{
		if (empty($_SERVER['REMOTE_ADDR']))
		{
			return;
		}
		
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$clientlist = preg_split('/,\s+?/', trim($_SERVER['HTTP_X_FORWARDED_FOR']));
			$whitelist = ae::options('request')->get('proxies',
				!empty($_SERVER['SERVER_ADDR']) ? array($_SERVER['SERVER_ADDR']) : ''
			);
			
			if (empty($whitelist))
			{
				return $_SERVER['REMOTE_ADDR'];
			}
			
			if (is_string($whitelist))
			{
				$whitelist = preg_split('/,\s+?/', trim($whitelist));
			}
			
			if (in_array($_SERVER['REMOTE_ADDR'], $whitelist))
			{
				return array_shift($clientlist);
			}
		}
		
		return $_SERVER['REMOTE_ADDR'];
	}
	
	protected static function _parse_request()
	{
		if (!empty(self::$_uri)) 
		{
			return;
		}
		
		$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
		
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
		
		$type = 'html';
		$uri = trim($uri, '/');
		
		if (preg_match('/\.([a-z0-9_]+)$/', $uri, $match) == 1)
		{
			$type = $match[1];
			$uri = substr($uri, 0, strlen($uri) - strlen($type) - 1);
		}
		
		
		self::$_uri = '/' . $uri . '.' . $type;
		self::$_type = $type;
		self::$_segments = array_map('urldecode', explode('/', $uri));
	}
}


class aeRoute
{
	protected static $depth = 0;
	protected static $segments = array();
	
	public static function request($segments = null)
	/*
		Returns are an instance of aeRequest object.
	*/
	{
		if (aeRequest::is_cli)
		{
			throw new aeRequestException("Cannot handle a non-HTTP request.");
		}
		
		if (!is_null($segments))
		{
			return new aeRequest(0, $segments);
		}
		
		return new aeRequest(self::$depth, aeRequest::segments());
	}
	
	protected $uri;
	protected $offset;
	protected $path;
	
	public function __construct($uri, $base)
	{
		$base = ae::resolve($base);
		
		if (!file_exists($base))
		{
			throw new aeRequestException('Request could not be routed. Base directory "'.$base.'" does not exist.');
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
	/*
		Returns true if the path can be routed.
	*/
	{
		return !is_null($this->path);
	}
	
	public function follow($parameters = array())
	/*
		Attempts to follow the path. Throws aeRequestException on error.
	*/
	{
		if (is_null($this->path))
		{
			throw new aeRequestException('Request could not be routed. No matches found.');
		}
		
		$depth = self::$depth + $this->offset;
		$ds = new aeSwitch(self::$depth, $depth);
		
		ae::output($this->path, $parameters);
	}
}

class aeRequestException extends Exception {}

// Calculate class constants.
define('__ae_request_cli__', defined('STDIN'));
define('__ae_request_ajax__', isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
	&& strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST');
define('__ae_request_method__', isset($_SERVER['REQUEST_METHOD']) ? 
	strtoupper($_SERVER['REQUEST_METHOD']) : 'UNKNOWN');
define('__ae_request_protocol__', isset($_SERVER['SERVER_PROTOCOL']) ? 
	strtoupper($_SERVER['SERVER_PROTOCOL']) : 'UNKNOWN');