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

ae::invoke('aeContainer');

class aeContainer
/*
	Wraps output of a script into an HTML container.
*/
{
	protected $path;
	protected $buffer;
	protected $vars = array();
	
	public function __construct($path)
	{
		if (empty($path))
		{
			throw new Exception('Decorator must be initialized with a path to the container.');
		}
		
		$this->path = $path;
		$this->buffer = new aeBuffer();
	}
	
	public function __destruct()
	{
		// TODO: Check how this works with a custom error handler
		$this->vars['content'] = $this->buffer->output();
		
		echo ae::render($this->path, $this->vars);
	}
	
	public function set($name, $value)
	{
		$this->vars[$name] = $value;
		
		return $this;
	}
}