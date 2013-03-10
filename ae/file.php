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

// TODO: File resource should be loaded lazily.
// TODO: Mode should be detected based on the first operation(s).
// FIXME: Should use chaining.
// FIXME: Should throw an exception on error.

ae::invoke('aeFile');

class aeFile
/*
	A thin wrapper that abstracts common file operations 
	mostly for the sake of simplicity and exception safety.
*/
{
	protected $path;
	protected $file;
	protected $is_locked = false;
	
	public function __construct($path)
	{
		$this->path = $path;
	}
	
	public function __destruct()
	{
		if ($this->file)
		{
			$this->close();
		}
	}
	
	public function open($mode)
	{
		$this->file = fopen($this->path, $mode);
		
		return $this->file !== false;
	}
	
	public function close()
	{
		if (!is_resource($this->file))
		{
			return true;
		}
		
		if ($this->is_locked)
		{
			$this->unlock();
		}
		
		$result = fclose($this->file);
		
		$this->file = null;
		
		return $result;
	}
	
	public function lock($mode = null)
	{
		if (!is_resource($this->file))
		{
			return false;
		}
		
		if ($this->is_locked)
		{
			$this->unlock();
		}
		
		if (is_null($mode))
		{
			$mode = LOCK_EX | LOCK_NB;
		}
		
		return $this->is_locked = (flock($this->file, $mode) === true);
	}
	
	public function unlock()
	{
		if (!$this->is_locked)
		{
			return true;
		}
		
		$this->is_locked = false;
		
		return flock($this->file, LOCK_UN);
	}
	
	public function truncate($size = 0)
	{
		if (!is_resource($this->file))
		{
			return false;
		}
		
		return ftruncate($this->file, $size);
	}
	
	public function write($content)
	{
		if (!is_resource($this->file))
		{
			return false;
		}
		
		return fwrite($this->file, $content) !== false;
	}
	
	public function read($length)
	{
		if (!is_resource($this->file))
		{
			return false;
		}
		
		return fread($this->file, $length);
	}
	
	public function seek($offset)
	{
		if (!is_resource($this->file))
		{
			return false;
		}
		
		return fseek($this->file, $offset) === 0;
	}
	
	public function offset()
	{
		if (!is_resource($this->file))
		{
			return false;
		}
		
		return ftell($this->file);
	}
}

class aeFileException extends Exception {}