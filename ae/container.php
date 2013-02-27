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

ae::invoke('aeContainer');

class aeContainer
/*
	Wraps output of a script with another script.
*/
{
	protected $path;
	protected $buffer;
	protected static $vars = array();
	protected static $stack = array();
	
	public function __construct($path)
	{
		if (empty($path))
		{
			throw new Exception('Container must be initialized with a path to the container script.');
		}
		
		$this->path = $path;
		$this->buffer = new aeBuffer();
		
		array_push(self::$stack, self::$vars);
	}
	
	public function __destruct()
	{
		self::$vars['content'] = $this->buffer->render();
		
		ae::output($this->path, self::$vars);
		
		self::$vars = array_pop(self::$stack);
	}
	
	public function set($name, $value)
	{
		self::$vars[$name] = $value;
		
		return $this;
	}
}