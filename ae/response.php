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
{
	protected $headers;
	protected $buffer;
	
	protected $status;
	protected $type;
	protected $charset;
	protected $compression;
	
	protected $cache_ttl;
	protected $cache_private;
	
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
		
		// Get buffered output and destroy the buffer
		$output = $this->buffer->output();
		unset($this->buffer);
		
		// Set caching headers
		$this->_cache();
		
		// Compress output, if browser supports compression
		$output = $this->_compress($output);
		
		// Content-*
		$this->header('Content-type', $this->type . '; charset=' . $this->charset)
			->header('Content-length', strlen($output));
		
		// Output headers
		foreach ($this->headers as $name => $value)
		{
			$prefix = empty($name) ? '' : $name . ': ';
			
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
	
	public function header($name, $value = null, $replace = true)
	/*
		Adds or replaces an HTTP header to the response.
	*/
	{
		if (is_null($value))
		{
			$value = $name;
			$name = '';
			$replace = false;
		}
		
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
	
	public function type($type = null)
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
				break;
			case 'css':
			case 'stylesheet':
				$this->type = 'text/css';
				break;
			case 'js':
			case 'javascript':
				$this->type = 'text/javascript';
				break;
			case 'text':
				$this->type = 'text/plain';
				break;
			case 'json':
				$this->type = 'application/json';
				break;
			case 'atom':
			case 'rdf':
			case 'rss':
			case 'xhtml':
				$this->type = 'application/'.$type.'+xml';
				break;
			case 'xml':
				$this->type = 'application/xml';
				break;
			default:
				$this->type = $type;
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
	
	public function cache($minutes, $private = false)
	/*
		Sets the time to live for client side caching. 
		Set `$private` to `true` to prevent proxy caching.
	*/
	{
		$this->cache_ttl = $minutes;
		$this->cache_private = $private;
		
		return $this;
	}
	
	protected function _cache()
	{
		if ($this->cache_ttl > 0)
		{
			$seconds = 60 * $this->cache_ttl;
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT')
				->header('Cache-Control', 'max-age='.$seconds.', ' . ($this->cache_private ? 'private' : 'public'))
				->header('Cache-Control', 'post-check=' . $seconds . ', pre-check=' . ($seconds * 2), false);
		}
		else
		{
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() - 365 * 24 * 60 * 60) . ' GMT')
				->header('Last-Modified', gmdate('D, d M Y H:i:s').' GMT')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate')
				->header('Cache-Control', 'post-check=0, pre-check=0');
		}
		
	}
	
	protected function _compress($output)
	/*
		Compresses the output if client supports it.
	*/
	{
		if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])
		|| strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') === false
		|| !function_exists('gzencode')
		|| ini_get('zlib.output_compression'))
		{
			return $output;
		}
		
		$encoding = strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'x-gzip') !== false ?
			'x-gzip' : 'gzip';

		$this->header('Vary','Accept-Encoding')
			->header('Content-Encoding', $encoding);

		return gzencode($output);
	}
}
