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

Core::invoke('\ae\Session');

class Session implements \ArrayAccess, \Iterator
/*
	Provides a standard safe(r) inteface to $_SESSION variable:
	
		$storage = Core::session('namespace'); // $_SESSION['namespace'];
		$storage['foo'] = 'bar'; // $_SESSION['namespace'] = 'bar';
		echo $storage['foo']; // echos 'bar';
	
*/
{
	protected $reference;
	
	public function __construct($namespace = null)
	{
		if (session_id() === '')
		{
			session_start();
		}
		
		if (is_string($namespace) && !empty($namespace))
		{
			$this->reference =& $_SESSION[$namespace];
		}
		else
		{
			$this->reference =& $_SESSION['__ae__'];
		}
	}

	// ==============================
	// = ArrayAccess implementation =
	// ==============================
	
	public function offsetExists($offset)
	{
		return isset($this->reference[$offset]);
	}
	
	public function offsetGet($offset)
	{
		if (isset($this->reference[$offset]))
		{
			return $this->reference[$offset];
		}
	}
	
	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) 
		{
			$this->reference[] = $value;
		}
		else
		{
			$this->reference[$offset] = $value;
		}
	}
	
	public function offsetUnset($offset)
	{
		unset($this->reference[$offset]);
	}
	
	// ===========================
	// = Iterator implementation =
	// ===========================
	
	public function rewind()
	{
		reset($this->reference);
	}
	
	public function current()
	{
		return current($this->reference);
	}
	
	public function key()
	{
		return key($this->reference);
	}
	
	public function next()
	{
		next($this->reference);
	}
	
	public function valid()
	{
		return !is_null(key($this->reference));
	}
}