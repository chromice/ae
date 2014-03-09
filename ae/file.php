<?php if (!class_exists('ae')) exit;

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

ae::invoke('aeFile');

class aeFile
/*
	A thin wrapper that abstracts common file operations 
	mostly for the sake of exception safety.
	
		$file = ae::file('example.txt')
			->open('w')
			->write('This is a test')
			->close();
	
	All methods throw `aeFileException` on failure.
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
		if (is_resource($this->file))
		{
			$this->close();
		}
	}
	
	public function exists()
	{
		return file_exists($this->path);
	}
	
	public function open($mode, $use_include_path = false, $context = null)
	{
		if (is_resource($this->file))
		{
			throw new aeFileException('File is already opened.');
		}
		
		$this->file = is_resource($context)
			? fopen($this->path, $mode, $use_include_path, $context)
			: fopen($this->path, $mode, $use_include_path);
		
		if (false === $this->file)
		{
			throw new aeFileException('Failed to open file.');
		}
		
		return $this;
	}
	
	public function close()
	{
		$this->_can('close file');
		
		if ($this->is_locked)
		{
			$this->unlock();
		}
		
		if (false === fclose($this->file))
		{
			throw new aeFileException('Failed to close file.');
		}
		
		$this->file = null;
		
		return $this;
	}
	
	public function lock($mode = null)
	{
		$this->_can('lock file');
		
		if ($this->is_locked)
		{
			$this->unlock();
		}
		
		if (is_null($mode))
		{
			$mode = LOCK_EX | LOCK_NB;
		}
		
		if (false === flock($this->file, $mode)) 
		{
			throw new aeFileException('Failed to lock file.');
		}
		
		$this->is_locked = true;
		
		return $this;
	}
	
	public function unlock()
	{
		$this->_can('unlock file');
		
		if (!$this->is_locked)
		{
			return $this;
		}
		
		if (false === flock($this->file, LOCK_UN))
		{
			throw new aeFileException('Failed to unlock file.');
		}
		
		$this->is_locked = false;
		
		return $this;
	}
	
	public function truncate($size = 0)
	{
		$this->_can('truncate file');
		
		if (false === ftruncate($this->file, $size))
		{
			throw new aeFileException('Failed to truncate file.');
		}
		
		return $this;
	}
	
	public function write($content, $length = null)
	{
		$this->_can('write to file');
		
		if (false === (is_null($length)
			? fwrite($this->file, $content)
			: fwrite($this->file, $content, $length)))
		{
			throw new aeFileException('Failed to write to file.');
		}
		
		return $this;
	}
	
	public function read($length)
	{
		$this->_can('read from file');
		
		if (false === fread($this->file, $length))
		{
			throw new aeFileException('Failed to write to file.');
		}

		return $this;
	}
	
	public function seek($offset, $whence = SEEK_SET)
	{
		$this->_can('seek the position');
		
		if (-1 === fseek($this->file, $offset, $whence))
		{
			throw new aeFileException('Failed to seek the position.');
		}
		
		return $this;
	}
	
	public function tell()
	{
		$this->_can('tell the position');
		
		if (flase === ($offset = ftell($this->file)))
		{
			throw new aeFileException('Failed to return the offset.');
		}
		
		return $offset;
	}
	
	protected function _can($intent)
	{
		if (!is_resource($this->file))
		{
			throw new aeFileException('Cannot ' . $intent . '. File is not opened.');
		}
	}
}

class aeFileException extends Exception {}