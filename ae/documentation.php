<?php

#
# Copyright 2011-2014 Anton Muraviev <chromice@gmail.com>
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

namespace ae;

Core::invoke('\ae\Documentation');

class Documentation
/*
	A response wrapper that serves captured content as text/x-markdown
	and (optionally) saves it to a file.
*/
{
	protected $base_uri;
	protected $base_dir;
	protected $save_as;
	
	protected $buffer;
	protected $examples = array();
	
	public function __construct($base_dir, $base_uri = null, $save_as = null)
	{
		$base_uri = is_null($base_uri) ? Core::request()->uri() : $base_uri;
		
		$this->base_dir = rtrim($base_dir, '/') . '/';
		$this->base_uri = empty($base_uri) ? '/' : '/' . trim($base_uri, '/') . '/';
		$this->save_as = $save_as;
		
		$this->buffer = new Buffer();
	}
	
	public function __destruct()
	{
		$output = $this->buffer->render();
		$response = Core::response('text/x-markdown');
		
		if (!empty($this->save_as))
		{
			$file = Core::file($this->base_dir . $this->save_as)
				->open('w')->write($output)->close();
		}
		
		echo $output;
		
		$response->dispatch();
	}
	
	public function example($path)
	/*
		Returns an example object for the given directory.
	*/
	{
		return $this->examples[] = new Example($path, $this->base_dir, $this->base_uri);
	}
	
	static public function diff($old, $new)
	/*
		Straight rip off [Paul's Simple Diff Algorithm v 0.1][1]. [See licence][2].
		
		[1]: https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
		[2]: https://github.com/paulgb/simplediff/blob/master/LICENSE
	*/
	{
		$matrix = array();
		$maxlen = 0;
		
		foreach ($old as $oindex => $ovalue)
		{
			$nkeys = array_keys($new, $ovalue);
			
			foreach($nkeys as $nindex)
			{
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
					$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				
				if ($matrix[$oindex][$nindex] > $maxlen)
				{
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}
		}
		
		if ($maxlen == 0)
		{
			return array(array('d' => $old, 'i' => $new));
		}
		
		return array_merge(
			self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
		);
	}
	
	static public function diff_lines($old, $new)
	/*
		Diffs lines in two bodies of text.
	*/
	{
		$ret = '';
		$diff = self::diff(preg_split("/\r\n|\n|\r/", $old), preg_split("/\r\n|\n|\r/", $new));
		
		foreach($diff as $k)
		{
			if (is_array($k))
			{
				$ret .= (!empty($k['d']) ? '-' . implode(' ', $k['d']) . "\n" : '') .
					(!empty($k['i']) ? '+' . implode(' ', $k['i']) . "\n" : '');
			}
			else
			{
				$ret .= ' ' . $k . "\n";
			}
		}
		return $ret;
	}
}

class Example
/*
	Represents code example.
*/
{
	protected $path;
	protected $base_uri;
	protected $base_dir;
	
	public function __construct($path, $base_dir, $base_uri)
	{
		$this->path = trim($path, '/');
		$this->base_dir = $base_dir;
		$this->base_uri = $base_uri;
	}
	
	public function __toString()
	{
		return (string) $this->source();
	}
	
	public function source($file_name = 'index.php', $type = null)
	{
		$path = $this->base_dir . $this->path . '/' . $file_name;
		
		if (!file_exists($path))
		{
			$message = 'File not found: ' . $this->path . '/' . $file_name;
			$message.= "\n" . str_repeat('=', strlen($message));
			
			return new SourceError($message, 'diff');
		}
		
		if (is_null($type))
		{
			$type = pathinfo($file_name, PATHINFO_EXTENSION);
		}
		
		return new Source(file_get_contents($path), $type);
	}
	
	// ===========
	// = Request =
	// ===========
	
	protected $request;
	
	public function on($uri, $data = null)
	/*
		Defines the request for the following expecated response.
		
			on('GET', array('foo' => 'bar'));
			on('POST', array('foo' => 'bar'));
			on(array(
				'method' => 'GET',
				'headers' => array(),
				'cookies' => array(),
				'data' => array('foo' => 'bar');
			));
	*/
	{
		
		$request = $this->_default_request();
		
		if (is_array($uri))
		{
			$request = array_replace($request, $uri);
		}
		elseif (preg_match('/^(GET|HEAD|POST|PUT|DELETE|TRACE|OPTIONS|CONNECT|PATCH) (.*)$/', $uri, $parts) > 0)
		{
			$request['method'] = $parts[1];
			$request['uri'] = $parts[2];
		}
		else
		{
			$request['method'] = 'GET';
			$request['uri'] = $uri;
		}
		
		if (is_array($data))
		{
			$request['data'] = $data;
		}
		
		$this->request = $request;
		
		return $this;
	}
	
	protected function _default_request()
	{
		return array(
			'method' => 'GET',
			'uri' => '/',
			'headers' => array(),
			'cookies' => array(),
			'data' => array()
		);
	}
	
	protected function _request()
	{
		if (empty($this->request))
		{
			return $this->_default_request();
		}
		
		$return = $this->request;
		
		unset($this->request);
		
		return $return;
	}
	
	protected function _request_url($uri = '')
	{
		return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' .
			"{$_SERVER['HTTP_HOST']}" . 
			$this->base_uri . $this->path . '/' . ltrim($uri, '/');
	}
	
	// ===============
	// = Expectation =
	// ===============
	
	public function expect($file_name)
	{
		/*
			Generate actual output via HTTP request
		*/
		$request = $this->_request();
		$r = curl_init();
		
		curl_setopt($r, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($r, CURLOPT_CUSTOMREQUEST, $request['method']);
		curl_setopt($r, CURLOPT_URL, $this->_request_url($request['uri']));
		
		if (!empty($request['data']))
		{
			curl_setopt($r, CURLOPT_POSTFIELDS, $request['data']);
		}
		
		if (!empty($request['headers']))
		{
			// TODO: Implement passing headers via cURL.
		}
		
		if (!empty($request['cookies']))
		{
			// TODO: Implement passing cookies via cURL.
		}
		
		// Receive actual output
		$actual = curl_exec($r);
		curl_close($r);
		
		/*
			Get the expected file
		*/
		$path = $this->base_dir . $this->path . '/' . $file_name;
		
		if (!file_exists($path))
		{
			$message = 'File not found: ' . $this->path . '/' . $file_name;
			$message.= "\n" . str_repeat('=', strlen($message));
			
			return new SourceError($message, 'diff');
		}
		
		$expected = file_get_contents($path);
		
		/*
			Compare the results
		*/
		if ($actual !== $expected)
		{
			$message = 'Unexpected output: ' . $this->path . '/' . $file_name;
			$message.= "\n" . str_repeat('=', strlen($message));
			
			return new SourceError("$message\n" . Documentation::diff_lines($expected, $actual), 'diff');
		}
		
		return new Source($actual, pathinfo($file_name, PATHINFO_EXTENSION));
	}
}

class Source
/*
	Represents source code of an example or expected output.
*/
{
	protected $source;
	protected $type;
	protected $lines = array();
	
	public function __construct($source, $type)
	{
		$this->source = $source;
		$this->type = $type;
	}
	
	public function __toString()
	{
		// TODO: Implement line slicing.
		$source = $this->source;
		
		if (!in_array('php', explode('+', $this->type)))
		{
			return "\n```{$this->type}\n" . $source . "\n```\n";
		}
		
		// Cut out hidden parts between '///+++' and '///---'
		if (preg_match_all('/^\s*\/{3}\s*(\-{3}|\+{3})\s*$/m', $source, $found, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) > 0)
		{
			$_source = '';
			$_offset = strlen($source);
			
			while ($f = array_pop($found))
			{
				$f_length = strlen($f[0][0]);
				$f_offset = $f[0][1];
				$f_type = $f[1][0];
				
				if ($f_type === '+++')
				{
					$_source = substr($source, ($f_offset + $f_length), ($_offset - ($f_offset + $f_length))) . $_source;
					$_offset = $f_offset;
				}
				elseif ($f_type === '---')
				{
					$_offset = $f_offset;
				}
			}
			
			$source = substr($source, 0, $_offset) . $_source;
		}
		
		// Remove non-comment sequence "///"
		$source = preg_replace('/^(\s*)\/{3}\s*(.*)$/m', '$1$2', $source);
	
		return "\n```{$this->type}\n" . $source . "\n```\n";
	}
	
	public function lines($start, $end)
	{
		$this->lines[] = array($start, $end);
		
		return $this;
	}
}

class SourceError extends Source
/*
	Exists only to stop line slicer from messing up the error message.
*/
{
	public function lines($start, $end)
	{
		return $this;
	}
}