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

ae::invoke('aeRequest', ae::singleton);

class aeRequest
/*
	Simple URI abstration.
*/
{
	protected $base_url = '/';
	protected $uri = array();
	
	public function __construct($uri = null, $base_url = '/')
	{
		if (is_null($uri))
		{
			// TODO: Detect uri automatically.
		}
		
		$this->base_url = $base_url;
		$this->uri = explode('/', trim($uri, '/'));
		array_map('urldecode', $this->uri);
	}
	
	public function __toString()
	{
		return $this->uri(0, null);
	}
	
	// =======
	// = URI =
	// =======
	
	public function uri($offset, $length = 1)
	{
		if (is_null($length) || ($length + $offset) > count($this->uri))
		{
			$length = count($this->uri) - $offset;
		}
		
		return implode('/',array_slice($this->uri, $offset, $length));
	}
	
	public function routeIn($base_dir)
	{
		$base_dir = ae::resolve($base_dir);
		
		if (!is_dir($base_dir))
		{
			trigger_error('Routing failed. Base directory "'.$base_dir.'" does not exist.', E_USER_ERROR);
		}
		
		$uri = $this->uri(0, null);
		$uri = $this->_rewrite($uri);
		$uri = explode('/', $uri);
		
		for ($l = count($uri); $l > 0; $l--)
		{ 
			$path = array_slice($uri, 0, $l);
			$path = implode('/', $path);
			$path = empty($path) ? 'index.php' : $path . '/index.php';
			$path = $base_dir . '/' . $path;
			
			if (file_exists($path))
			{
				echo ae::render($path);
				
				return true;
			}
		} 
		
		return false; // or TRUE if request was routed successfully
	}
	
	public function href()
	/*
		Makes a valid URI from its arguments.
		One segment per argument.
	*/
	{
		$segments = func_get_args();
		
		return $this->base_url . $this->_rewrite(implode('/', $segments), true);
	}
	
	protected $rules = array();
	protected $rules_reverse = array();
	
	public function alias($from, $to)
	/*
		Adds the rule to the list and remaps this URI.
	*/
	{
		$from = trim($from, '/');
		$to = trim($to, '/');
		
		$_from = '/^' .preg_quote($from,'/') .'/';
		$_to = '/^' .preg_quote($to,'/') .'/';
		
		$this->rules[$_from] = $to;
		$this->rules_reverse[$_to] = $from;
		
		return $this;
	}
	
	protected function _rewrite($uri, $reverse = false)
	/*
		Does the actual mapping.
	*/
	{
		$uri = trim($uri, '/');
		
		if (count($this->rules) === 0)
		{
			return $uri;
		}
		
		if (!$reverse)
		{
			return preg_replace(array_keys($this->rules), $this->rules, $uri);
		}
		else
		{
			return preg_replace(array_keys($this->rules_reverse), $this->rules_reverse, $uri);
		}
	}
}
