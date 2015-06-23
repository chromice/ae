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
	protected $root;
	protected $path;
	
	public function __construct()
	{
		$this->root = rtrim(\ae::options('ae::path')->get('root'), '/') . '/';
		$this->path = 
	}
	
	public function __toString()
	{
		return $this->path;
	}
	
	protected function _path($paths, $relative = false)
	{
		# code...
	}
	
	public function path()
	{
		# code...
	}
	
	public function relative_path()
	{
		# code...
	}
	
	public function exists()
	{
		return file_exists($this->path);
	}
	
	public function is_directory()
	{
		return is_file($this->path);
	}
	
	public function is_file()
	{
		return is_file($this->path);
	}
	
	public function directory($path = null)
	{
		# code...
	}
	
	public function file($path = null)
	{
		# code...
	}
}
