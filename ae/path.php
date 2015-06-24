<?php

#
# Copyright 2011-2015 Anton Muraviev <chromice@gmail.com>
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

\ae::options('ae::path', [
	'root' => dirname(__DIR__)
]);

\ae::invoke('\ae\Path');

class Path
{
	protected $path;
	
	public function __construct()
	{
		$this->path = self::_path(func_get_args(), false);
	}
	
	public function __toString()
	{
		return $this->path;
	}
	
	// ------------------
	// - Public methods -
	// ------------------
	
	public function path()
	{
		return new Path($this->path, self::_path(func_get_args()));
	}
	
	public function exists()
	{
		return file_exists($this->path);
	}
	
	public function is_directory()
	{
		return is_dir($this->path);
	}
	
	public function is_file()
	{
		return is_file($this->path);
	}
	
	public function directory()
	{
		# code...
	}
	
	public function file($name = null)
	{
		ae::file();
	}
	
	// -------------------
	// - Private methods -
	// -------------------
	
	protected static function _root()
	{
		return rtrim(\ae::options('ae::path')->get('root'), '/') . '/';
	}
	
	protected static function _path($paths, $relative = true)
	{
		# code...
	}
}

class PathException extends Exception {}
