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

// TODO: Shall we have aeRequest::argument(s) method for $_GET and custom requests?
ae::invoke(array('aeRoute','request'), ae::factory);

class aeRequest
/*
	Simple request abstration.
	
	`request` options:
		`proxies`	-	an array or comma-separated list of IP addresses.
*/
{
	const is_cli = __ae_request_cli__;
	const is_ajax = __ae_request_ajax__;
	const method = __ae_request_method__;
	const protocol = __ae_request_protocol__;
	
	protected $type = 'html';
	protected $depth = 0;
	protected $segments = array();
	
	public function __construct($depth, $segments, $type)
	{
		$this->type = $type;
		$this->depth = $depth;
		$this->segments = is_array($segments) ? $segments : explode('/', trim($segments, '/'));
		
		$this->segments = array_map(function($segment) {
			return ae::escape($segment, ae::identifier);
		}, $this->segments);
	}
	
	public function is_routed()
	/*
		Returs TRUE if requested was routed.
	*/
	{
		return $this->depth > 0;
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
		
	public function type()
	/*
		Returns the file type of the request, `html` by default. Top level 
	*/
	{
		return $this->type;
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
		$offset += $this->depth;
		
		if (is_null($length) || ($length + $offset) > count($this->segments))
		{
			$length = count($this->segments) - $offset;
		}
		
		$uri = implode('/', array_slice($this->segments, $offset, $length));
		
		return new aeRoute($uri, $base);
	}
	
	public static function uri()
	/*
		Returns current URI string.
	*/
	{
		static $uri;
		
		if (!empty($uri))
		{
			return $uri;
		}
		
		$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
		
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
		
		$uri = '/' . trim($uri, '/');
		
		return $uri;
	}
	
	public static function segments()
	/*
		Returns an array of current URI segments.
	*/
	{
		static $segments;
		
		if (!empty($segments))
		{
			return $segments;
		}
		
		$segments = explode('/', trim(self::uri(), '/'));
		$segments = array_map('urldecode', $segments);
		
		return $segments;
	}
	
	public static function arguments()
	/*
		Returns an array of command line arguments.
	*/
	{
		if (empty($_SERVER['argv']))
		{
			return array();
		}
		
		$segments = $_SERVER['argv'];
		array_shift($segments);
		
		return $segments;
	}
}


class aeRoute
{
	protected static $type;
	protected static $depth = 0;
	protected static $segments = array();
	
	public static function request($segments = null)
	/*
		Returns are an instance of aeRequest object.
	*/
	{
		// Custom request
		if (!is_null($segments))
		{
			if (empty(self::$type))
			{
				$type = self::_parse_type($segments);
			}
			
			return new aeRequest(0, $segments, $type);
		}
		
		// Obtain segments
		if (empty(self::$segments))
		{
			self::$depth = 0;
			self::$segments = aeRequest::is_cli ? aeRequest::arguments() : aeRequest::segments();
		}
		
		if (empty(self::$type))
		{
			self::$type = self::_parse_type(self::$segments);
		}
		
		return new aeRequest(self::$depth, self::$segments, self::$type);
	}
	
	protected static function _parse_type(&$segments)
	/*
		Returns the type of the requested resource.
	*/
	{
		$last = array_pop($segments);
		$type = 'html';
		
		if (!is_null($last))
		{
			if (preg_match('/\.([a-z0-9_]+)$/', $last, $match) == 1)
			{
				$type = $match[1];
				$last = substr($last, 0, strlen($last) - strlen($type) - 1);
			}
			
			array_push($segments, $last);
		}
		
		return $type;
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
			throw new aeRequestException('Request could not be routed. No matching file exists.');
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