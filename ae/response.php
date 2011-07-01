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
	
	protected $http_statuses = array(
		100 => '100 Continue',
		101 => '101 Switching Protocols',

		//  Success
		200 => '200 OK',
		201 => '201 Created',
		202 => '202 Accepted',
		203 => '203 Non-Authoritative Information',
		204 => '204 No Content',
		205 => '205 Reset Content',
		206 => '206 Partial Content',

		//  Redirects
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		303 => '303 See Other',
		304 => '304 Not Modified',
		305 => '305 Use Proxy',
		// 306 => '306 (Unused)',
		307 => '307 Temporary Redirect',

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
	
	public function __construct()
	{
		$this->reset();
		$this->buffer = new aeBuffer();
	}
	
	public function __destruct()
	{
		$this->send();
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
			->charset('utf-8');
	}
	
	public function send()
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
			header($protocol . ' ' . $this->http_statuses[$this->status]);
		}
		
		// Get buffered output
		$output = $this->buffer->output();
		unset($this->buffer);
		
		// Compress output, if possible
		// TODO: Make compression level user configurable.
		$output = $this->_compress($output, 5);
		
		// Content-*
		$this
			->header('Content-type', $this->type . '; charset='.$this->charset)
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
	
	public function status($status)
	/*
		Sets response status.
	*/
	{
		$status = (int)$status;
		
		if (isset($this->http_statuses[$status]))
		{
			$this->status = $status;
		}
		
		return $this;
	}
	
	public function type($type)
	/*
		Sets content type of the response.
	*/
	{
		$type = strtolower($type);
		
		switch ($type)
		{
			case 'html':
				$this->type = 'text/html'; break;
			case 'css':
			case 'stylesheet':
				$this->type = 'text/css'; break;
			case 'javascript':
			case 'json':
			case 'js':
				$this->type = 'text/javascript'; break;
			case 'text':
				$this->type = 'text/plain'; break;
			case 'atom':
			case 'rdf':
			case 'rss':
			case 'xhtml':
				$this->type = 'application/'.$type.'+xml'; break;
			case 'xml':
				$this->type = 'application/xml'; break;
			default:
				$this->type = $type;
		}
		
		return $this;
	}
	
	public function charset($charset)
	/*
		Sets charset of the response.
	*/
	{
		$this->charset = $charset;
		
		return $this;
	}
	
	protected function _compress($output, $level)
	/*
		Attempts to compress the output
		if the recipient can handle this.
	*/
	{
		if ($level == 0 
			|| !isset($_SERVER['HTTP_ACCEPT_ENCODING'])
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

		return gzencode($output, $level, FORCE_GZIP);
	}
}
