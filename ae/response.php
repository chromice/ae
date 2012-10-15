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

ae::invoke('aeResponse');

class aeResponse
/*
	`response` options:
		`directory`	-	path to directory to put cache files to: '/cache' by default;
		`compress`	-	whether to gzip dispatched output: true or false (default);
		`charset`	-	character set: 'utf-8' by default.
*/
{
	protected $headers;
	protected $buffer;
	
	public function __construct($type)
	{
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
				// FIXME: Obsolete mimetype in RFC 4329 
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

		$this->header('Content-type', $type);
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
		if (ae::options('response')->get('compress', false))
		{
			$output = $this->_compress($output);
		}
		
		// Copression affects "Content-Length"
		$this->header('Content-Length', strlen($output));
		
		$this->_cache_headers('private');
		
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

		$this->header('Vary', 'Accept-Encoding')
			->header('Content-Encoding', $encoding);

		return gzencode($output);
	}
	
	/*
		Caching
	*/
	
	protected $cache_ttl;
	protected $cache_headers_set = false;
	
	public function cache($minutes)
	/*
		Enables caching and sets "time to live".
	*/
	{
		$this->cache_ttl = $minutes;
		
		return $this;
	}
	
	protected function _cache_headers($cc = 'private')
	{
		if ($this->cache_headers_set) 
		{
			return;
		}
		
		$this->cache_headers_set = true;
		
		if ($this->cache_ttl > 0)
		{
			$seconds = 60 * $this->cache_ttl;
			
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT')
				->header('Last-Modified', null)
				->header('Cache-Control', 'max-age=' . $seconds . ', ' . ($cc === 'private' ? $cc : 'public'))
				->header('Cache-Control', 'post-check=' . $seconds . ', pre-check=' . ($seconds * 2), false);
		}
		else
		{
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() - 365 * 24 * 60 * 60) . ' GMT')
				->header('Last-Modified', gmdate('D, d M Y H:i:s').' GMT')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate')
				->header('Cache-Control', 'post-check=0, pre-check=0', false);
		}
	}
	
	public function save($uri)
	/*
		Saves reponse to cache directory.
	*/
	{
		if ($this->cache_ttl < 1 || !($cache_path = self::_cache_directory()))
		{
			return $this;
		}
		
		$this->_cache_headers('public');
		
		// Determine file path and extension
		$uri = trim($uri, '/');
		$ext = 'html';
		
		if (!empty($uri) && preg_match('/^(.*?)\.([a-z0-9_]+)$/', $uri, $match) == 1)
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
		
		// Get output
		$output = $this->buffer->render();

		$htaccess = ae::file($cache_path . '.htaccess');
		$content = ae::file($cache_path . 'index.' . $ext);
		
		if (!$htaccess->open('w') || !$content->open('w')
		|| !$htaccess->lock(LOCK_EX | LOCK_NB) 
		|| !$content->lock(LOCK_EX | LOCK_NB))
		{
			return;
		}

		$ts = date('YmdHis', time() + $this->cache_ttl * 60);

		// Create rewrite rules...
		$rules = "<IfModule mod_rewrite.c>
	RewriteEngine on

	RewriteCond %{ENV:REDIRECT_FROM_ROOT} !1 [OR]
	RewriteCond %{TIME} >$ts
	RewriteRule ^(.*) /index.php?/$1 [L]\n";

		// ...and dump headers
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
		
		// Add compression rules
		if (ae::options('response')->get('compress', false))
		{
			$rules.= "\n<IfModule mod_deflate.c>
	SetOutputFilter DEFLATE

	BrowserMatch ^Mozilla/4 gzip-only-text/html
	BrowserMatch ^Mozilla/4\.0[678] no-gzip
	BrowserMatch \bMSIE !no-gzip !gzip-only-text/html

	Header append Vary User-Agent env=!dont-vary\n";
			
			$rules.= "</IfModule>";
		}
		
		// Write content to files
		if (!$htaccess->write($rules) || !$content->write($output))
		{
			trigger_error('Coult not write to cache files.', E_USER_WARNING);
			
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
	
	protected static function _cache_directory()
	/*
		Returns cache directory path and checks if it is writable.
	*/
	{
		$cache_path = trim(ae::options('response')->get('directory', '/cache'), '/');
		
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
			if (in_array($file, array('.','..')))
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
