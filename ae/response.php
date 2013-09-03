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

ae::invoke('aeResponse');

class aeResponse
/*
	`response` options:
		`compress_output` - whether to gzip dispatched output (FALSE by default);
		`charset`         - character set; 'utf-8' by default;
		`error_path`      - path to a script that is used by `aeResponse::error()`.
*/
{
	protected $headers;
	protected $buffer;
	
	protected $current_switch;
	protected static $current;
	
	public function __construct($type = null)
	{
		$this->current_switch = new aeSwitch(self::$current, $this);
		
		$this->headers = array();
		$this->buffer = new aeBuffer();

		if (empty($type))
		{
			$type = 'html';
		}

		$type = strtolower(trim($type));
		
		switch ($type)
		{
			case 'html':
				$type = 'text/html';
				break;
			case 'css':
			case 'stylesheet':
				$type = 'text/css';
				break;
			case 'csv':
				$type = 'text/csv';
				break;
			case 'js':
			case 'javascript':
				// FIXME: Obsolete mimetype according to RFC 4329 
				$type = 'text/javascript';
				break;
			case 'txt':
			case 'text':
				$type = 'text/plain';
				break;
			case 'atom':
			case 'rdf':
			case 'rss':
			case 'soap':
			case 'xhtml':
				$type = 'application/' . $type . '+xml';
				break;
			case 'json':
			case 'pdf':
			case 'postscript':
			case 'xml':
				$type = 'application/' . $type;
				break;
		}
		
		// Append character set
		$type.= '; charset=' . ae::options('response')->get('charset', 'utf-8');

		// Set content type
		$this->header('Content-Type', $type);
		
		// Disable caching by default
		$this
			->header('Expires', gmdate('D, d M Y H:i:s', time() - aeResponseCache::year * 60) . ' GMT')
			->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
			->header('Cache-Control', 'max-age=0, no-cache, must-revalidate, proxy-revalidate');
	}

	public static function current()
	/*
		Returns currently active response object.
	*/
	{
		return self::$current;
	}
	
	protected $http_errors = array(
		// Client errors
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		402 => '402 Payment Required',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		406 => '406 Not Acceptable',
		407 => '407 Proxy Authentication Required',
		408 => '408 Request Timeout',
		409 => '409 Conflict',
		410 => '410 Gone',
		411 => '411 Length Required',
		412 => '412 Precondition Failed',
		413 => '413 Request Entity Too Large',
		414 => '414 Request-URI Too Long',
		415 => '415 Unsupported Media Type',
		416 => '416 Requested Range Not Satisfiable',
		417 => '417 Expectation Failed',
		
		// Server errors
		500 => '500 Internal Server Error',
		501 => '501 Not Implemented',
		502 => '502 Bad Gateway',
		503 => '503 Service Unavailable',
		504 => '504 Gateway Timeout',
		505 => '505 HTTP Version Not Supported'
	);
	
	public function error($code, $path = null)
	/*
		Responds with an 4xx or 50x HTTP error code and halts execution.
	*/
	{
		// Reset the buffer
		$this->buffer->reset();
		
		// Validate the code
		$code = (int) $code;
		
		if (empty($this->http_errors[$code]))
		{
			trigger_error('Unknown HTTP error code: ' . $code, E_USER_NOTICE);
			
			$code = 500;
		}
		
		// Respond with a correct header
		header((isset($_SERVER['SERVER_PROTOCOL']) 
			? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1')
			. ' ' . $this->http_errors[$code]);
		
		if (empty($path)) 
		{
			$path = ae::options('response')->get('error_path', $path);
		}
		
		if (!empty($path)) 
		{
			ae::output($path, array('code' => $code, 'status' => $this->http_errors[$code]));
		}
		
		exit;
	}
	
	public function header($name, $value, $replace = true)
	/*
		Adds or replaces an HTTP header to the response.
	*/
	{
		if (isset($this->headers[$name]) && $replace === false)
		{
			$this->headers[$name][] = $value;
		}
		else if (empty($value) && $replace === true)
		{
			unset($this->headers[$name]);
		}
		else
		{
			$this->headers[$name] = array($value);
		}
		
		return $this;
	}
	
	public function cache($minutes, $uri = null)
	/*
		Sets caching headers and (optionally) save response to web cache.
	*/
	{
		$this
			->header('Expires', gmdate('D, d M Y H:i:s', time() + 60 * $minutes) . ' GMT')
			->header('Last-Modified', null)
			->header('Cache-Control', !is_null($uri) ? 'public' : 'private');
		
		if (!is_null($uri))
		{
			$cache = new aeResponseCache();
			
			$cache
				->duration($minutes)
				->headers($this->headers)
				->content($this->buffer->render())
				->save($uri);
			
			unset($cache);
		}
		
		return $this;
	}
	
	public function dispatch()
	/*
		Dispatches the response to the browser and halts execution.
	*/
	{
		if (!isset($this->buffer))
		{
			return;
		}
		
		// Get buffered output and destroy the buffer
		$output = $this->buffer->render();
		unset($this->buffer);
		
		// Compress output, if browser supports compression
		if (ae::options('response')->get('compress_output', false))
		{
			$output = $this->_compress($output);
			
			// Compression affects "Content-Length"
			$this->header('Content-Length', strlen($output));
		}
		
		// Output headers
		foreach ($this->headers as $name => $value)
		{
			$prefix = $name . ': ';
			
			header($prefix . array_shift($value));
			
			foreach ($value as $_value)
			{
				header($prefix . $_value, false);
			}
		}
		
		echo $output;
		exit;
	}
	
	protected function _compress($output)
	/*
		Compresses the output if client supports it.
	*/
	{
		if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])
		|| strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false
		|| !function_exists('gzencode')
		|| ini_get('zlib.output_compression'))
		{
			return $output;
		}
		
		$encoding = strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false ?
			'x-gzip' : 'gzip';

		$this
			->header('Vary', 'Accept-Encoding')
			->header('Content-Encoding', $encoding);

		return gzencode($output);
	}
}

class aeResponseCache
/*
	`cache` options:
		`directory_path`  - path to directory where cache files are stored ('/cache' by default);
*/
{
	const day = 1440;
	const week = 10080;
	const month = 43200;
	const year = 525600;
	
	protected $ttl = 0;
	protected $headers = array();
	protected $content = '';
	
	public function duration($minutes)
	/*
		Sets for how long cache is valid in minutes.
	*/
	{
		$this->ttl = $minutes;
		
		return $this;
	}
	
	public function headers($headers)
	/*
		Sets HTTP headers of the cached response.
	*/
	{
		foreach ($headers as &$value)
		{
			$value = (array) $value;
		}
		
		$this->headers = $headers;
		
		return $this;
	}
	
	public function content($content)
	/*
		Sets content of the cached response.
	*/
	{
		$this->content = $content;
		
		return $this;
	}
	
	public function save($uri)
	/*
		Saves response to cache.
	*/
	{
		if ($this->ttl === 0 || !($cache_path = self::_cache_directory()))
		{
			return $this;
		}
		
		// Determine file path and extension
		$uri = trim($uri, '/');
		$ext = 'html';
		
		if (!empty($uri) && preg_match('/^(.*?)\.([a-z0-9_]+)$/', $uri, $match) === 1)
		{
			$uri = $match[1];
			$ext = $match[2];
		}
		
		$cache_path = $cache_path . '/' .
			(!empty($uri) ? $uri . '/' : '') .
			'index.' . $ext . '/';

		// Create directory, if necessary
		if (!is_dir($cache_path))
		{
			mkdir($cache_path, 0777, true);
		}
		
		// Try opening and locking the files
		try 
		{
			$htaccess = ae::file($cache_path . '.htaccess')
				->open('w')
				->lock();
				
			$content = ae::file($cache_path . 'index.' . $ext)
				->open('w')
				->lock();
		} 
		catch (aeFileException $e) 
		{
			return $this;
		}

		$ts = date('YmdHis', time() + $this->ttl * 60);

		// Create rewrite rules...
		$rules = "<IfModule mod_rewrite.c>
	RewriteEngine on

	RewriteCond %{ENV:REDIRECT_FROM_ROOT} !1 [OR]
	RewriteCond %{TIME} >$ts
	RewriteRule ^(.*) index.php?/$1 [L]\n";

		// ...and dump headers
		foreach ($this->headers as $name => $value)
		{
			foreach ($value as $_value)
			{
				$rules.= "\n\tHeader set " . $name . ' "' . $_value . '"';
			}
		}
		
		$rules.= "\n</IfModule>";
		
		// Add compression rules
		if (ae::options('response')->get('compress_output', false)
		&& preg_match('/^(?:gif|jpe?g|png)$/', $ext) === 0)
		{
			$rules.= "\n<IfModule mod_deflate.c>
	SetOutputFilter DEFLATE

	BrowserMatch ^Mozilla/4 gzip-only-text/html
	BrowserMatch ^Mozilla/4\.0[678] no-gzip
	BrowserMatch \bMSIE !no-gzip !gzip-only-text/html

	Header append Vary User-Agent env=!dont-vary\n";
			
			$rules.= "</IfModule>";
		}
		
		// Try writing content to files
		try
		{
			$htaccess->write($rules);
			$content->write($this->content);
		}
		catch (aeFileException $e) 
		{
			trigger_error('Coult not write cache files.', E_USER_NOTICE);
			
			unset($htaccess, $content);
			self::delete($cache_path);
		}

		return $this;
	}
	
	public static function delete($uri)
	/*
		Deletes all matched responses.
	*/
	{
		if (!($cache_path = self::_cache_directory()))
		{
			return;
		}
		
		$uri = trim($uri, '/');
		$cache_path = $cache_path . '/' . (!empty($uri) ? $uri . '/' : '');
		
		if (is_dir($cache_path))
		{
			self::_remove_directory($cache_path);
		}
	}
	
	public static function collect_garbage()
	/*
		Deletes stale cache entries.
		
		NB! This operation is IO intensive and should be performed infrequently on an internal thread.
	*/
	{
		if (!($cache_path = self::_cache_directory()))
		{
			return;
		}
		
		self::_clean_directory($cache_path);
	}
	
	protected static function _clean_directory($path)
	/*
		Recursively scans the directory for stale cache entries.
	*/
	{
		$path = rtrim($path, '/');
		
		if (file_exists($path . '/.htaccess') 
		&& preg_match('/RewriteCond %{TIME} >(\d{14})/', file_get_contents($path . '/.htaccess'), $matches))
		{
			if ($matches[1] < date('YmdHis'))
			{
				self::_remove_directory($path);
			}
		}
		else foreach (scandir($path) as $dir)
		{
			if (in_array($dir, array('.', '..')))
			{
				continue;
			}
			
			$dir = $path . '/' . $dir;
			
			if (is_dir($dir))
			{
				self::_clean_directory($dir);
			}
		}
	}
	
	protected static function _cache_directory()
	/*
		Returns cache directory path and checks if it is writable.
	*/
	{
		$cache_path = trim(ae::options('cache')->get('directory_path', '/cache'), '/');
		
		if (empty($cache_path))
		{
			return;
		}
		
		$cache_path = realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $cache_path);
		
		// Check if directory is writable
		if (is_dir($cache_path) && is_writable($cache_path))
		{
			return $cache_path;
		}
		
		trigger_error('Cache directory "' . $cache_path . '" is not writable.', E_USER_NOTICE);
	}
	
	protected static function _remove_directory($path)
	/*
		Attempts to remove the directory.
		
		NB! May fail silently, if a file is locked.
	*/
	{
		foreach (scandir($path) as $file)
		{
			if (in_array($file, array('.', '..')))
			{
				continue;
			}
			
			$file = $path . '/' . $file;
			
			if (is_dir($file))
			{
				self::_remove_directory($file);
			}
			else 
			{
				@unlink($file);
			}
		}
		
		@rmdir($path);
	}
}
