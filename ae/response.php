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

ae::invoke('aeResponse', ae::singleton);

class aeResponse
/*
	`response` options:
		`cache_dir`	-	path to directory to put cache files to;
		`compress`	-	true | false to gzip the output.
*/
{
	protected $headers;
	protected $buffer;
	
	protected $status;
	protected $type;
	protected $extension;
	protected $charset;
	
	protected $http_statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',

		//  Success
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		//  Redirects
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 => '(Unused)',
		307 => ' Temporary Redirect',

		// Client errors
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Server errors
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);
	
	public function __construct()
	{
		$this->reset();
		$this->buffer = new aeBuffer();
	}
	
	public function __destruct()
	{
		$this->finish();
	}
	
	public function reset()
	{
		if (isset($this->buffer))
		{
			unset($this->buffer);
			$this->buffer = new aeBuffer();
		}

		$this->headers = array();
		
		return $this->status(200)
			->type('html')
			->charset('utf-8')
			->cache(0);
	}
	
	public function finish()
	/*
		Sends the response to the browser and halts the execution.
	*/
	{
		if (!isset($this->buffer))
		{
			return;
		}
		
		// HTTP status response 
		if ($this->status !== 200 && isset($this->http_statuses[$this->status]))
		{
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
			header($protocol . ' ' . $this->status . ' ' . $this->http_statuses[$this->status]);
		}
		
		// Set mime-type and character set
		$this->header('Content-type', $this->type . '; charset=' . $this->charset);
		
		// Set cache-related headers
		if ($this->cache_ttl > 0 && $this->status === 200)
		{
			$seconds = 60 * $this->cache_ttl;
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT')
				->header('Cache-Control', 'max-age='.$seconds.', ' . (!$this->cache_static ? 'private' : 'public'))
				->header('Cache-Control', 'post-check=' . $seconds . ', pre-check=' . ($seconds * 2), false);
		}
		else
		{
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() - 365 * 24 * 60 * 60) . ' GMT')
				->header('Last-Modified', gmdate('D, d M Y H:i:s').' GMT')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate')
				->header('Cache-Control', 'post-check=0, pre-check=0');
		}
		
		// Get buffered output and destroy the buffer
		$output = $this->buffer->output();
		unset($this->buffer);
		
		$o = ae::options('response');
		
		// Cache to disk
		if ($this->status === 200 && $cache_dir = $o->get('cache_dir', null))
		{
			$this->_cache($output, $cache_dir);
		}
		
		// Compress output, if browser supports compression
		if ($o->get('compress', false))
		{
			$output = $this->_compress($output);
		}
		
		// Copression affects "Content-Length"
		$this->header('Content-Length', strlen($output));
		
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
		
		// Output content
		echo $output;
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
		else
		{
			$this->headers[$name] = array($value);
		}
		
		return $this;
	}
	
	public function status($status = null)
	/*
		$status = status(); // returns current status.
		status($status); // Sets response status.
	*/
	{
		if (is_null($status))
		{
			return $this->status;
		}
		
		$status = (int)$status;
		
		if (isset($this->http_statuses[$status]))
		{
			$this->status = $status;
		}
		
		return $this;
	}
	
	public function type($type = null, $extension = null)
	/*
		$type = type(); // returns current mime-type.
		type($type); // Sets mime-type of the response. [chainable]
		
		Aliases are supported, e.g. "html", "css", "json", etc.
	*/
	{
		if (is_null($type))
		{
			return $this->type;
		}
		
		$type = strtolower($type);
		
		switch ($type)
		{
			case 'html':
				$this->type = 'text/html';
				$this->extension = 'html';
				break;
			case 'css':
			case 'stylesheet':
				$this->type = 'text/css';
				$this->extension = 'css';
				break;
			case 'js':
			case 'javascript':
				$this->type = 'text/javascript';
				$this->extension = 'js';
				break;
			case 'text':
				$this->type = 'text/plain';
				$this->extension = 'txt';
				break;
			case 'json':
				$this->type = 'application/json';
				$this->extension = 'json';
				break;
			case 'atom':
			case 'rdf':
			case 'rss':
			case 'xhtml':
				$this->type = 'application/'.$type.'+xml';
				$this->extension = $type;
				break;
			case 'xml':
				$this->type = 'application/xml';
				$this->extension = 'xml';
				break;
			default:
				$this->type = $type;
				$this->extension = null;
		}
		
		if (!is_null($extension))
		{
			$this->extension = $extension;
		}
		
		return $this;
	}
	
	public function charset($charset = null)
	/*
		$charset = charset(); // returns current charset.
		charset($charset); // Sets charset of the response. [chainable]
		
		Sets charset of the response.
	*/
	{
		if (is_null($charset))
		{
			return $this->charset;
		}
		
		$this->charset = $charset;
		
		return $this;
	}

	/*
		Caching
	*/
	
	protected $cache_ttl;
	protected $cache_static;
	protected $cache_base;
	protected $cache_uri;
	protected $cache_type;
	
	public function cache($minutes, $static = false)
	/*
		Sets the time to live for client side caching. 
		
		If `$static` is `true`, proxy caching is enabled.
		if `$static` is string, file caching is enabled.
		
		If aeRequest library is used for rooting, `$static` 
		must be relative to the last route.
	*/
	{
		$r = ae::request();
		
		if (!$r->is('get'))
		{
			return $this;
		}
		
		$this->cache_ttl = $minutes;
		$this->cache_static = (bool) $static;
		
		$this->cache_uri = is_string($static) ? $static : '';
		$this->cache_base = $r->base();
		
		return $this;
	}
	
	protected function _cache($output, $cache_dir)
	/*
		Caches output to disk.
	*/
	{
		if ($this->cache_ttl < 1 || !$this->cache_static)
		{
			return;
		}
		
		$cache_dir = realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . trim($cache_dir, '/'));
		
		if (is_null($cache_dir) || !is_dir($cache_dir) || !is_writable($cache_dir))
		{
			trigger_error("Cache directory is not writable.", E_USER_NOTICE);
			
			return;
		}
		
		$ext = $this->extension;
		$uri = $this->cache_uri;
		
		// Has user specified extension as well?
		if (!empty($uri) && preg_match('/^(.*?)\.([a-z0-9_]+)$/', $uri, $match) == 1)
		{
			$uri = $match[1];
			$ext = $match[2];
		}
		
		
		$dir_path = $cache_dir . '/' .
			(!empty($this->cache_base) ? $this->cache_base . '/' : '') .
			(!empty($uri) ? $uri . '/' : '') .
			'index.' . $ext . '/';
		
		if (!is_dir($dir_path))
		{
			mkdir($dir_path, 0777, true);
		}
			
		
		$htaccess = fopen($dir_path . '.htaccess', "c");
		$content = fopen($dir_path . 'index.' . $ext, "c");
		
		// Try to lock .htaccess file
		if (!flock($htaccess, LOCK_EX | LOCK_NB))
		{
			fclose($htaccess);
			fclose($content);
			
			return;
		}

		// Try to lock content file
		if (!flock($content, LOCK_EX | LOCK_NB))
		{
			flock($htaccess, LOCK_UN); fclose($htaccess);
			fclose($content);
			
			return;
		}
		
		$ts = gmdate('YmdHis', time() + $this->cache_ttl * 60);
		
		// Create basic rewrite rules
		$rules = "<IfModule mod_rewrite.c>
	RewriteEngine on

	RewriteCond %{ENV:REDIRECT_FROM_ROOT} !1 [OR]
	RewriteCond %{TIME} >$ts
	RewriteRule ^(.*) /index.php?/$1 [L]\n";

		foreach ($this->headers as $name => $value)
		{
			$prefix = $name;
			
			$rules.= "\n\tHeader add " . $name . ' "' . array_shift($value) . '"';
			
			foreach ($value as $_value)
			{
				$rules.= "\n\tHeader add " . $name . ' "' . $_value . '"';
			}
		}
		
		$rules.= "\n</IfModule>";
		
		
		// Dump output into content file
		ftruncate($htaccess, 0);
		
		if (fwrite($htaccess, $rules) === FALSE)
		{
			trigger_error('Coult not write cache rules file.', E_USER_WARNING);
		}
		
		// Dump output into content file
		ftruncate($content, 0);
		
		if (fwrite($content, $output) === FALSE)
		{
			trigger_error('Coult not write content cache file.', E_USER_WARNING);
		}
		
		// Close files
		flock($htaccess, LOCK_UN); fclose($htaccess);
		flock($content, LOCK_UN); fclose($content);
	}
	
	/*
		gzip compression
	*/
	
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

		$this->header('Vary','Accept-Encoding')
			->header('Content-Encoding', $encoding);

		return gzencode($output);
	}
}
