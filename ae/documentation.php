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
	
*/
{
	protected $buffer;
	protected $base_uri;
	protected $base_path;
	protected $examples = array();
	
	public function __construct($base_path = null)
	{
		$this->buffer = new Buffer();
		$this->base_uri = Core::request()->uri();
		$this->base_path = rtrim($base_path);
	}
	
	public function __destruct()
	{
		$response = Core::response('text');
		
		echo $this;
		
		$response->dispatch();
	}
	
	public function __toString()
	{
		return (string) $this->buffer->render();
	}
	
	public function save($path)
	/*
		Saves documentation output
	*/
	{
		return $this;
	}
	
	public function explains($path)
	/*
		Calculates code coverage for the source file.
	*/
	{
		return $this;
	}
	
	public function example($path)
	/*
		Returns an example object.
		
		See: Example.
	*/
	{
		$path = $this->base_path . '/' . ltrim($path, '/');
		
		return $this->examples[] = new Example($path, $this->base_uri);
	}
}

class Example
/*
	
*/
{
	protected $source_path;
	protected $output_path;
	protected $base_uri;
	protected $base_path;
	
	public function __construct($source_path, $base_uri)
	{
		$this->source_path = $source_path;
		$this->base_uri = $base_uri;
		$this->base_path = dirname($source_path);
	}
	
	public function __toString()
	{
		if (!empty($this->output_path))
		{
			$output = Core::file(Core::resolve($this->output_path));
			
			return "\n```" . $output->extension(false) . "\n" . $output . "\n```\n";
		}
		else
		{
			$source = (string) Core::file(Core::resolve($this->source_path));
			
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
			
			// Remove implied comment sequence "///"
			$source = preg_replace('/^(\s*)\/{3}\s*(.*)$/m', '$1$2', $source);
		
			return "\n```php\n" . $source . "\n```\n";
		}
	}
	
	public function provided($misc)
	/*
		Defines the condition that, if not met, skips the rest of the tests.
	*/
	{
		return $this;
	}
	
	public function when($uri, $data = null)
	/*
		Defines the parameters under which the following outputs()
		or throws() statement is true.
		
			$e->when('/some/uri');
			$e->when('/some/uri', array('foo' => 'bar'));
			$e->when('POST /some/uri', array('foo' => 'bar'));
	*/
	{
		if (preg_match('/^(GET|HEAD|POST|PUT|DELETE|TRACE|OPTIONS|CONNECT|PATCH) (.*)/', $uri, $parts) > 0)
		{
			$method = $parts[1];
			$uri = $parts[2];
		}
		else
		{
			$method = 'GET';
		}
		
		$uri = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' .
			"{$_SERVER['HTTP_HOST']}" . 
			$this->base_uri . '/' . ltrim($uri, '/');
		
		$this->condition = array(
			'method' => $method,
			'uri' => $uri,
			'data' => $data
		);
		
		return $this;
	}
	
	public function outputs($output_path)
	/*
		
	*/
	{
		$this->output_path = $this->base_path . '/' . ltrim($output_path, '/');
		
		if (!empty($this->condition))
		{
			$expected_output = Core::file(Core::resolve($this->output_path));
			
			$r = curl_init();
			
			curl_setopt($r, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($r, CURLOPT_CUSTOMREQUEST, $this->condition['method']);
			curl_setopt($r, CURLOPT_URL, $this->condition['uri']);
			
			if (!empty($this->condition['data']))
			{
				curl_setopt($r, CURLOPT_POSTFIELDS, $this->condition['data']);
			}
			
			$actual_output = curl_exec($r);
			curl_close($r);
			
			if (trim($actual_output) !== trim($expected_output))
			{
				trigger_error('Output did not match!', E_USER_ERROR);
			}
			
			unset($this->condition);
		}
		
		return $this;
	}
	
	public function throws($exception, $message = null)
	/*
		
	*/
	{
		return $this;
	}
}