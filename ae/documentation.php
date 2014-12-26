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

\ae::invoke('\ae\Documentation');

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
		$base_uri = is_null($base_uri) ? \ae::request()->uri() : $base_uri;
		
		$this->base_dir = rtrim($base_dir, '/') . '/';
		$this->base_uri = empty($base_uri) ? '/' : '/' . trim($base_uri, '/') . '/';
		$this->save_as = $save_as;
		
		$this->buffer = new Buffer();
	}
	
	public function __destruct()
	{
		// Show code coverage
		$this->_show_code_coverage();
		
		$output = $this->buffer->render();
		$response = \ae::response('text/x-markdown');
		
		/*
			Gather coverage stats
		*/
		$covered = count(array_filter($this->covered_stats));
		$total = count($this->covered_stats);
		$average = $covered > 0 ? round(100 * array_reduce($this->covered_stats, function ($c, $i) {
			return $c + $i;
		}, 0) / $covered, 2) : 0;
		
		$coverage_summary = "$covered out of $total files covered (**$average%** average coverage)";
		$coverage_details = array(
			'| ' . str_pad('File', 20) . '|' . ' Coverage |',
			'|' . str_repeat('-', 21) . '|' . str_repeat('-', 9) . ':|'
		);
		
		foreach ($this->covered_stats as $file => $percent)
		{
			$coverage_details[] = '| ' . str_pad($file, 20) . '| ' . 
				str_pad(round(100 * $percent, 2) . '%', 9) . '|';
		}
		
		$coverage_details = implode("\n", $coverage_details);
		
		/*
			Gather test stats
		*/
		$tests_total = 0;
		$tests_failed = 0;
		
		foreach ($this->examples as $example)
		{
			$tests_total += $example->tests_total;
			$tests_failed += $example->tests_failed;
		}
		
		$tests_passed = number_format($tests_total - $tests_failed);
		$tests_passed_percent = round(1 - $tests_failed / $tests_total, 4) * 100 . '%';
		$tests_total = number_format($tests_total);
		
		$tests_summary = "$tests_passed out of {$tests_total} passed (**{$tests_passed_percent}** passed)";
		
		/*
			Replace stat tokens
		*/
		$output = str_replace(array_map(function ($i) {
			return '{' . $i . '}';
		}, array(
			'tests:summary',
			'coverage:summary',
			'coverage:details',
		)), array(
			$tests_summary,
			$coverage_summary,
			$coverage_details
		), $output);
		
		
		if (!empty($this->save_as))
		{
			$ts = date('d F Y H:i:s');
			$file = \ae::file($this->base_dir . $this->save_as)
				->open('w')
				->write($output . "\n<!-- Generated on $ts -->")
				->close();
		}
		
		echo $output;
		
		$response->dispatch();
	}
	
	static public function analyzer()
	/*
		Returns a code coverage analyzer.
	*/
	{
		return new Analyzer();
	}
	
	public function example($path)
	/*
		Returns an example object for the given directory.
	*/
	{
		return $this->examples[] = new Example($path, $this->base_dir, $this->base_uri);
	}
	
	
	// =================
	// = Code coverage =
	// =================
	
	protected $covered_files = array();
	protected $covered_stats = array();
	static protected $code_coverage = array();
	
	static public function merge_code_coverage($info)
	{
		foreach ($info as $file => $lines)
		{
			foreach ($lines as $line => $value)
			{
				$pointer =& self::$code_coverage[$file][$line];
				
				if (empty($pointer))
				{
					$pointer = $value;
				}
				else
				{
					$pointer = max($value, $pointer);
				}
			}
		}
	}
	
	public function covers($path, $show = false)
	/*
		Enables code coverage calculation for that file.
	*/
	{
		$this->covered_files[$path] = $show;
		
		return $this;
	}
	
	protected function _show_code_coverage()
	{
		$files = array_filter($this->covered_files);
		
		if (count($files) > 0)
		{
			// Show heading
			echo "\n\n* * *\n\n# Code coverage\n\n";
		}
		
		foreach ($this->covered_files as $path => $print)
		{
			$path = ltrim($path);
			$real_path = $this->base_dir . ltrim($path, '/');
			
			if (!file_exists($real_path))
			{
				$message = 'File not found: ' . $path;
				$message.= "\n" . str_repeat('=', strlen($message));
			
				echo new SourceError($message, 'diff');
				
				continue;
			}
			
			$real_path = realpath($real_path);
			
			$this->covered_stats[$path] = isset(self::$code_coverage[$real_path])
				? 1 - array_reduce(self::$code_coverage[$real_path], function ($c, $i) {
					return $c += $i === -1 ? 1 : 0;
				}, 0) / count(self::$code_coverage[$real_path])
				: null;
			
			if ($print === false)
			{
				continue;
			}
			
			if (!isset(self::$code_coverage[$real_path]))
			{
				$message = 'No code coverage for ' . $path;
				$message.= "\n" . str_repeat('=', strlen($message));
			
				echo new SourceError($message, 'diff');
				
				continue;
			}
			
			$source = 'Code coverage for ' . $path;
			$source.= "\n" . str_repeat('=', strlen($source));
			
			$script = preg_split("/\r\n|\n|\r/", file_get_contents($real_path));
			$coverage = self::$code_coverage[$real_path];
			
			foreach ($script as $index => $code)
			{
				if (!isset($coverage[$index + 1]))
				{
					$code = ' ' . $code;
				}
				elseif ($coverage[$index + 1] > 0)
				{
					$code = '+' . $code;
				}
				elseif ($coverage[$index + 1] === -1)
				{
					$code = '-' . $code;
				}
				else
				{
					$code = ' ' . $code;
				}
				
				$source.= "\n" . $code;
			}
			
			echo new Source($source, 'diff');
		}
	}
	
	
	// ==========================
	// = Calculating difference =
	// ==========================
	
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
				$ret .= (!empty($k['d']) ? '-' . implode("\n-", $k['d']) . "\n" : '') .
					(!empty($k['i']) ? '+' . implode("\n+", $k['i']) . "\n" : '');
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
	
	public $tests_total = 0;
	public $tests_failed = 0;
	
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
		$this->tests_total++;
		$path = $this->base_dir . $this->path . '/' . $file_name;
		
		if (!file_exists($path))
		{
			$message = 'File not found: ' . $this->path . '/' . $file_name;
			$message.= "\n" . str_repeat('=', strlen($message));
			
			$this->tests_failed++;
			
			return new SourceError($message, 'diff');
		}
		
		$lint = exec('php -l ' . $path);
		
		if (strpos($lint, 'No syntax errors detected') === FALSE)
		{
			$this->tests_failed++;
			
			return new SourceError($lint, 'txt');
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
		$this->tests_total++;
		
		$actual = $this->_execute();
		
		/*
			Get the expected file
		*/
		$path = $this->base_dir . $this->path . '/' . $file_name;
		
		if (!file_exists($path))
		{
			$message = 'File not found: ' . $this->path . '/' . $file_name;
			$message.= "\n" . str_repeat('=', strlen($message));
			
			$this->tests_failed++;
			
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
			
			$this->tests_failed++;
			
			return new SourceError("$message\n" . Documentation::diff_lines($expected, $actual), 'diff');
		}
		
		return new Source($actual, pathinfo($file_name, PATHINFO_EXTENSION));
	}
	
	public function contains($contains, $type = 'txt')
	{
		$this->tests_total++;
		
		$actual = $this->_execute();
		
		/*
			Compare the results
		*/
		if (strpos($actual, $contains) === false)
		{
			$message = 'Expected to find: ' . $contains;
			$message.= "\n" . str_repeat('=', strlen($message));
			
			$this->tests_failed++;
			
			return new SourceError("$message\n" . $actual, 'diff');
		}
		
		return new Source($actual, $type);
	}
	
	public function outputs($expected, $type = 'txt')
	{
		$this->tests_total++;
		
		$actual = $this->_execute();
		
		/*
			Compare the results
		*/
		if ($actual !== $expected)
		{
			$message = 'Unexpected output';
			$message.= "\n" . str_repeat('=', strlen($message));
			
			$this->tests_failed++;
			
			return new SourceError("$message\n" . Documentation::diff_lines($expected, $actual), 'diff');
		}
		
		return new Source($actual, $type);
	}
	
	protected function _execute()
	/*
		Generates an HTTP request and returns actual output.
	*/
	{
		$request = $this->_request();
		$r = curl_init();
		
		curl_setopt($r, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($r, CURLOPT_HEADER, true);
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
			// TODO: Pass session id cookie by default.
		}
		
		// Receive actual output + headers
		$response = curl_exec($r);
		list($header, $body) = explode("\r\n\r\n", $response, 2);
		curl_close($r);
		
		// Retrieve code coverage information
		if (preg_match('/^X-Code-Coverage:\s*(.+)$/m', $header, $found) === 1)
		{
			Documentation::merge_code_coverage(unserialize(base64_decode($found[1])));
		}
		
		return $body;
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
	protected $replacements = array();
	
	public function __construct($source, $type)
	{
		$this->source = $source;
		$this->type = $type;
	}
	
	public function __toString()
	{
		$source = preg_split("/\r\n|\n|\r/", $this->source);
		
		// Slice the lines.
		if (!empty($this->lines))
		{
			$_source = array();
			ksort($this->lines, SORT_NUMERIC);
		
			foreach ($this->lines as $from => $to)
			{
				array_splice($_source, count($_source), 0, array_slice($source, $from - 1, $to - $from + 1));
			}
			
			$source = $_source;
		}
		
		$source = implode("\n", $source);
		
		// TODO: Indent with spaces.
		// TODO: Reduce extra indentation.
		
		if (!in_array('php', explode('+', $this->type)))
		{
			return "\n```{$this->type}\n" . $source . "\n```\n";
		}
		
		// Replace __DIR__ with 'path/to'
		$source = preg_replace('/__DIR__\s*\.\s*([\'"])/', '$1path/to', $source);
		
		// Replace other strings in the source
		$source = str_replace(array_keys($this->replacements), $this->replacements, $source);
		
		// Cut out hidden parts between '///---' and '///+++'
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
	
	public function lines($from, $to)
	{
		if (is_int($from) && is_int($to) && $from <= $to)
		{
			$this->lines[$from] = $to;
		}
		
		return $this;
	}
	
	public function replace($search, $replace)
	{
		$this->replacements[$search] = $replace;
		
		return $this;
	}
}

class SourceError extends Source
/*
	Stops line slicer from messing up the error message.
*/
{
	public function __construct($source, $type)
	{
		parent::__construct($source, $type);
		
		echo parent::__toString();
	}
	
	public function __toString()
	{
		return '';
	}
	
	public function lines($start, $end)
	{
		return $this;
	}
}

class Analyzer
/*
	Code coverage analyzer.
	
	Starts analyzing code coverage when object is instantiated.
*/
{
	static protected $count = 0;
	
	public function __construct()
	{
		if (self::$count === 0)
		{
			self::_start();
		}
		
		self::$count++;
	}
	
	public function __destruct()
	{
		self::$count--;
		
		if (self::$count > 0)
		{
			return;
		}
		
		$coverage = self::_get();
		self::_stop();
		
		Documentation::merge_code_coverage($coverage);
		
		if (!headers_sent())
		{
			header('X-Code-Coverage: ' . base64_encode(serialize($coverage)));
		}
	}
	
	static protected function _start()
	{
		if (function_exists('xdebug_start_code_coverage'))
		{
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
		}
	}
	
	static protected function _stop()
	{
		if (function_exists('xdebug_stop_code_coverage'))
		{
			xdebug_stop_code_coverage();
		}
	}
	
	static protected function _get()
	{
		if (function_exists('xdebug_get_code_coverage'))
		{
			return xdebug_get_code_coverage();
		}
	}
}