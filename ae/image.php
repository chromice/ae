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

// TODO: Add ability to change background color of the destination image (including opacity).
// TODO: Add ability to change quality of the output JPEG.
// FIXME: scale() and fit() must accept NULL as one parameter.

ae::invoke('aeImage');

class aeImage
{
	protected $path;
	
	public function __construct($path)
	{
		$this->path = ae::resolve($path);

		$info = getimagesize($this->path);
		
		if (false === $info)
		{
			throw new Exception('Could not load image data for ' . $this->path);
		}
		
		$this->width = $info[0];
		$this->height = $info[1];
		$this->type = $info[2];
		$this->mimetype = $info['mime'];
	}
	
	public function __destruct()
	{
		$this->_unload();
	}

	/*
		Image data
	*/
	
	protected $width;
	protected $height;
	protected $type;
	protected $mimetype;
	
	public function width()
	{
		return $this->width;
	}
	
	public function height()
	{
		return $this->height;
	}
	
	public function type()
	{
		switch ($this->type)
		{
			case IMAGETYPE_GIF: return 'gif';
			case IMAGETYPE_PNG: return 'png';
			case IMAGETYPE_JPEG: return 'jpeg';
		}
	}
	
	public function mimetype()
	{
		return $this->mimetype;
	}
	
	/*
		Manipulation
	*/
	
	const left = 0;
	const center = 0.5;
	const right = 1;
	
	const top = 0;
	const middle = 0.5;
	const bottom = 1;
	
	protected $align_x = 0.5;
	protected $align_y = 0.5;
	
	protected $source;
	protected $source_width;
	protected $source_height;
	
	public function align($horizontal, $vertial)
	{
		$this->align_x = min(1, max(0, (float)$horizontal));
		$this->align_y = min(1, max(0, (float)$vertial));
		
		return $this;
	}
	
	public function cover($width, $height)
	{
		$this->_load();
		
		return $this;
	}
	
	public function crop($width, $height)
	{
		$this->_load();
		
		$destination = imagecreatetruecolor($width, $height);
		$success = imagecopyresampled(
			$destination,
			$this->source,
			0, 0, // destination: x, y
			round(($this->source_width - $width) * $this->align_x), // source: x
			round(($this->source_height - $height) * $this->align_y), // source: y
			$width, $height, // destination: width, height
			$width, $height  // source: width, height
		);
		
		if ($success) 
		{
			$this->_unload();
			
			$this->source = $destination;
			$this->source_width = $width;
			$this->source_height = $height;
		}
		else
		{
			throw new Exception('Failed to crop the image.');
		}
		
		return $this;
	}
	
	public function fit($width, $height)
	{
		return $this;
	}
	
	public function scale($width, $height)
	{
		$this->_load();
		
		$destination = imagecreatetruecolor($width, $height);
		$success = imagecopyresampled(
			$destination,
			$this->source,
			0, 0, // destination: x, y
			0, 0, // source: x, y
			$width, $height, // destination: width, height
			$this->source_width, $this->source_height  // source: width, height
		);
		
		if ($success) 
		{
			$this->_unload();
			
			$this->source = $destination;
			$this->source_width = $width;
			$this->source_height = $height;
		}
		else
		{
			throw new Exception('Failed to scale the image.');
		}
		
		return $this;
	}
	
	public function apply($filter, $arg_1 = null, $arg_2 = null, $arg_3 = null, $arg_4 = null)
	{
		$this->_load();
		
		imagefilter($this->source, $filter, $arg_1, $arg_2, $arg_3, $arg_4);
		
		return $this;
	}
	
	protected function _load()
	{
		if (!is_null($this->source))
		{
			return;
		}
		
		$this->source_width = $this->width;
		$this->source_height = $this->height;
		
		switch ($this->type)
		{
			case IMAGETYPE_GIF:
				$this->source = imagecreatefromgif($this->path);
				break;
			case IMAGETYPE_PNG:
				$this->source = imagecreatefrompng($this->path);
				break;
			case IMAGETYPE_JPEG:
				$this->source = imagecreatefromjpeg($this->path);
				break;
		}
		
		if (false === $this->source) 
		{
			throw new Exception('Failed to load the image: ' . $this->path);
		}
	}
	
	protected function _unload()
	{
		if (!is_null($this->source))
		{
			imagedestroy($this->source);
		}
		
		$this->source = null;
	}
	
	/*
		Output
	*/
	protected $suffix;
	protected $prefix;
	
	public function suffix($suffix)
	{
		$this->suffix = $suffix;
		
		return $this;
	}
	
	public function prefix($prefix)
	{
		$this->prefix = $prefix;
		
		return $this;
	}
	
	public function save($name = null)
	{
		$type = $this->type;
		$parts = pathinfo($this->path);
		
		if (!is_null($name))
		{
			$path = $parts['dirname'] . '/' . $name;
			
			switch (substr($name, strrpos($name, '.'))) 
			{
				case 'gif': $type = IMAGETYPE_GIF; break;
				case 'png': $type = IMAGETYPE_PNG; break;
				case 'jpeg': 
				case 'jpg': $type = IMAGETYPE_JPEG; break;
			}
		}
		else
		{
			$path = $parts['dirname'] . '/' 
				. $this->prefix 
				. $parts['filename']
				. $this->suffix
				. '.' . $parts['extension'];
		}
		
		switch ($type)
		{
			case IMAGETYPE_GIF:
				$success = imagegif($this->source, $path);
				break;
			case IMAGETYPE_PNG:
				$success = imagepng($this->source, $path);
				break;
			case IMAGETYPE_JPEG:
				$success = imagejpeg($this->source, $path);
				break;
		}
		
		if (!$success) 
		{
			throw new Exception('Failed to save the image: ' . $this->path);
		}
		
		return $this;
	}

	public function dispatch()
	{
		exit;
	}
}