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

// TODO: cached copy should be compressed if appropriate apache module is available.
ae::invoke('aeResponse');

class aeResponse
/*
	`response` options:
		`directory`	-	path to directory to put cache files to;
		`compress`	-	true | false; whether to gzip dispatched output;
		`charset`	-	character set; utf-8 by default.
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
			case 'text/html':
				$type = 'text/html';
				break;
			case 'css':
			case 'stylesheet':
				$type = 'text/css';
				break;
			case 'js':
			case 'javascript':
				$type = 'text/javascript';
				break;
			case 'txt':
			case 'text':
				$type = 'text/plain';
				break;
			case 'json':
				$type = 'application/json';
				break;
			case 'atom':
			case 'rdf':
			case 'rss':
			case 'xhtml':
				$type = 'application/'.$type.'+xml';
				break;
			case 'xml':
				$type = 'application/xml';
				break;
		}
		
		// Set content type and character set
		$this->header('Content-type', $type . '; charset=' . ae::options('response')->get('charset', 'utf-8'));
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
		$output = $this->buffer->content();
		unset($this->buffer);
		
		// Compress output, if browser supports compression
		if (ae::options('response')->get('compress', false))
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
	protected $cache_private;
	
	public function cache($minutes, $private = true)
	/*
		Sets the "time to live" for client-side caching. 
		
		If `$private` is `false`, proxy caching is enabled.
	*/
	{
		$this->cache_ttl = $minutes;
		$this->cache_private = (bool) $private;
		
		if ($this->cache_ttl > 0)
		{
			$seconds = 60 * $this->cache_ttl;
			
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT')
				->header('Last-Modified', null)
				->header('Cache-Control', 'max-age=' . $seconds . ', ' . ($this->cache_private ? 'private' : 'public'))
				->header('Cache-Control', 'post-check=' . $seconds . ', pre-check=' . ($seconds * 2), false);
		}
		else
		{
			$this->header('Expires', gmdate('D, d M Y H:i:s', time() - 365 * 24 * 60 * 60) . ' GMT')
				->header('Last-Modified', gmdate('D, d M Y H:i:s').' GMT')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate')
				->header('Cache-Control', 'post-check=0, pre-check=0', false);
		}
		
		return $this;
	}
	
	public function save($path)
	/*
		Saves reponse to cache directory.
	*/
	{
		if ($this->cache_ttl < 1 || $this->cache_private 
		|| !($cache_path = self::_cache_directory()))
		{
			return $this;
		}
		
		// Determine file path and extension
		$path = trim($path, '/');
		$ext = 'html';
		
		if (!empty($path) && preg_match('/^(.*?)\.([a-z0-9_]+)$/', $path, $match) == 1)
		{
			$path = $match[1];
			$ext = $match[2];
		}
		
		$cache_path = $cache_path . '/' .
			(!empty($path) ? $path . '/' : '') .
			'index.' . $ext . '/';

		// Create directory, if necessary
		if (!is_dir($cache_path))
		{
			mkdir($cache_path, 0777, true);
		}
		
		// Get output
		$output = $this->buffer->content();

		// TODO: File open, close and lock must use ae::file()
		$htaccess = ae::file($cache_path . '.htaccess');
		$content = ae::file($cache_path . 'index.' . $ext);
		
		if (!$htaccess->open('c') || !$content->open('c')
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
		
		$htaccess->truncate(0);
		$content->truncate(0);

		if (!$htaccess->write($rules) || !$content->write($output))
		{
			trigger_error('Coult not write to cache files.', E_USER_WARNING);
			
			unset($htaccess, $content);
			self::delete($cache_path);
		}
		
		return $this;
	}
	
	public static function delete($path)
	/*
		Deletes all matched responses.
	*/
	{
		if (!($cache_path = self::_cache_directory()))
		{
			return;
		}
		
		$path = trim($path, '/');
		$cache_path = $cache_path . '/' . (!empty($path) ? $path . '/' : '');
		
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
		Attempts to remove the directory. May fail silently, if a file is locked.
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
