<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2015 Anton Muraviev <anton@goodmoaning.me>
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

ae::options('ae.request', array(
	'base_url' => '/',
	'proxy_ips' => !empty($_SERVER['SERVER_ADDR']) ? array($_SERVER['SERVER_ADDR']) : null
));

ae::invoke(array('aeRouter', 'request'));

class aeRequest
/*
	Provides easy access to URI segments and allows request routing and redirection.
	
	Provided the application is accessed with "/some/arbitrary/request.json":
	
		$request = ae::request();
		
		echo $request->segment(0); // some
		echo $request->segment(1); // arbitrary
		
		echo $request->type(); // json
		echo $request->segment(99, 'default'); // default
	
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
		Returns TRUE, if requested was routed.
	*/
	{
		return $this->depth > 0;
	}
	
	public function base()
	/*
		Returns the base part of the routed URI.
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
	
	public function route($rules, $target = null)
	/*
		Returns an instance of `aeRouter` for current URI.
	
		Throws `aeRequestException`, if target path cannot be resolved.
	*/
	{
		$uri = implode('/', array_slice($this->segments, $this->depth));
		
		if (!is_array($rules))
		{
			$rules = array($rules => $target);
		}
		
		return new aeRouter($uri, $rules);
	}
	
	// ================================
	// = URL generation and redirects =
	// ================================
	
	const permanently = 301;
	const temporarily = 302;
	
	public static function url($uri = null)
	/*
		Returns a URI, prefixed with base URL.
		
			ae::options('ae.request')->set('base_url', 'https://domain.com/')
			echo $request::url('blah'); // echo "https://domain.com/blah"
	*/
	{
		return self::_base_url() . '/' . ltrim((is_null($uri) ? aeRequest::uri() : $uri), '/');
	}
	
	public static function redirect($uri, $http_response_code = aeRequest::temporarily)
	/*
		Redirects to a specific URI.
	*/
	{
		header("Location: " . self::url($uri), true, $http_response_code);
		exit;
	}
	
	// ==============================
	// = Global request information =
	// ==============================
	
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
		Returns IP address of the client. 
		
		If the server is behind a proxy or load balancer, this method will
		return its IP address, unless it has been added to the list of 
		known proxies.
	*/
	{
		if (empty($_SERVER['REMOTE_ADDR']))
		{
			return;
		}
		
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$clientlist = preg_split('/,\s+?/', trim($_SERVER['HTTP_X_FORWARDED_FOR']));
			$whitelist = ae::options('ae.request')->get('proxy_ips');
			
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
		if (!is_null(self::$_uri)) 
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
			
			if ($var !== 'PATH_INFO' && $uri !== $script_name)
			{
				$uri = str_replace($script_name, '', $uri);
			}
		
			if ($uri)
			{
				break;
			}
		}
		
		$type = 'html';
		$uri = urldecode(trim($uri, '/'));
		$base_path = self::_base_url();
		
		if (strlen($base_path) > 0 && strpos($uri, $base_path) === 0)
		{
			$uri = trim(substr($uri, strlen($base_path)), '/');
		}
		
		if (preg_match('/\.([a-z0-9_]+)$/', $uri, $match) == 1)
		{
			$type = $match[1];
			$uri = substr($uri, 0, strlen($uri) - strlen($type) - 1);
		}
		
		if (empty($uri))
		{
			self::$_uri = '';
			self::$_type = $type;
			self::$_segments = array();
		}
		else
		{
			self::$_uri = '/' . $uri . ($type !== 'html' ? '.' . $type  : '');
			self::$_type = $type;
			self::$_segments = explode('/', $uri);
		}
	}
	
	protected static function _base_url()
	{
		return rtrim(ae::options('ae.request')->get('base_url'), '/');
	}
}


class aeRouter
/*
	Provides URI routing capabilities to `aeRequest`.
	
	You can route a request based on its URI to either a closure or
	a directory:
	
		ae::request()->route(array(
			'/special/{any}/{alpha}/{numeric}' => function ($any, $alpha, $numeric, $etc) {
				echo 'Special request URI: /special/' . $any . '/' . $alpha . '/' . $numeric . '/' . $etc;
			},
			'/' => 'webroot/'
		))->follow();
	
*/
{
	protected static $depth = 0;
	protected static $segments = array();
	
	public static function request($segments = null)
	/*
		Returns an instance of `aeRequest` object for given segments.
	
		Throws `aeRequestException` for non-HTTP requests.
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
	
	// ===========
	// = Routing =
	// ===========
	
	protected $offset;
	protected $path;
	
	protected $callback;
	protected $arguments = array();
	
	public function __construct($uri, $rules)
	{
		$uri = trim($uri, '/');
		
		foreach ($rules as $rule => $route) 
		{
			$rule = strtr(preg_quote(trim($rule, '/'), '/'), array(
				'\{alpha\}' => '([a-zA-Z]+)',
				'\{numeric\}' => '([0-9]+)',
				'\{any\}' => '([^\/]+)'
			));
			
			if (preg_match('/^' . $rule . '\\/?(.*)$/', $uri, $matches) === 1)
			{
				if (is_string($route)) 
				{
					$this->_route_uri($route, end($matches));
				} 
				elseif (is_callable($route)) 
				{
					array_shift($matches);
					$this->callback = $route;
					$this->arguments = $matches;
				}
				
				break;
			}
		}
	}

	protected function _route_uri($base, $uri)
	{
		try 
		{
			$base = ae::resolve($base);
		} 
		catch (Exception $e) 
		{
			throw new aeRequestException('Request could not be routed. Base directory "' . $base . '" does not exist.');
		}
		
		if (is_file($base))
		{
			$this->path = $base;
			$this->offset = 0;
			
			return;
		}
		
		$uri = trim($uri, '/');
		$segments = explode('/', $uri);
		$extensions = array('.' . aeRequest::type() . '.php', '.php');
		
		for ($l = count($segments); $l > 0; $l--)
		{
			foreach ($extensions as $ext)
			{
				$path = implode('/', array_slice($segments, 0, $l));
				$path.= is_dir($base . '/' . $path) ? '/index' . $ext : $ext;
				$path = $base . '/' . ltrim($path, '/');
		
				if (file_exists($path))
				{
					$this->path = $path;
					$this->offset = $l;
					
					return;
				}
			}
		} 
	}
		
	public function exists()
	/*
		Returns TRUE, if route exists.
	*/
	{
		return !is_null($this->path) || !is_null($this->callback);
	}
	
	public function follow()
	/*
		Attempts to route the request.
		
		Throws `aeRequestException`, if there is no path to follow.
	*/
	{
		if (is_callable($this->callback))
		{
			return call_user_func_array($this->callback, $this->arguments);
		}
		
		if (is_null($this->path))
		{
			throw new aeRequestException('Request could not be routed. No matches found.');
		}
		
		$depth = self::$depth + $this->offset;
		$ds = new aeSwitch(self::$depth, $depth);
		
		ae::output($this->path);
	}
}

class aeRequestException extends aeException {}

// Calculate class constants.
define('__ae_request_cli__', defined('STDIN'));
define('__ae_request_ajax__', isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
	&& strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST');
define('__ae_request_method__', isset($_SERVER['REQUEST_METHOD']) ? 
	strtoupper($_SERVER['REQUEST_METHOD']) : 'UNKNOWN');
define('__ae_request_protocol__', isset($_SERVER['SERVER_PROTOCOL']) ? 
	strtoupper($_SERVER['SERVER_PROTOCOL']) : 'UNKNOWN');
