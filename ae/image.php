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

ae::import('ae/response.php');

ae::invoke('aeImage');

class aeImage
/*
	An image manipulation library.
	
	The following example would crop the top part of the specified image 
	to a 100x100 square thumbail and save it as "image_thumb.png":
	
		ae::image('path/to/image.png')
			->align(aeImage::center, aeImage::top)
			->fill(100, 100)
			->suffix('_thumb')
			->save(); 
	
	Constructor throw `Exception`, if path to image cannot be resolved.
	All methods throw `aeImageException`, if operation is not successful.
*/
{
	protected $path;
	
	public function __construct($path)
	{
		$this->path = ae::resolve($path);

		$info = getimagesize($this->path);
		
		if (false === $info)
		{
			throw new aeImageException('Could not load image data for ' . $this->path);
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

	// ==============
	// = Image data =
	// ==============
	
	protected $width;
	protected $height;
	protected $type;
	protected $mimetype;
	
	public function width()
	/*
		Returns the width of the image.
	*/
	{
		return $this->width;
	}
	
	public function height()
	/*
		Returns the height of the image.
	*/
	{
		return $this->height;
	}
	
	public function type()
	/*
		Returns the type of the image as a string: "gif", "png" or "jpeg".
	*/
	{
		switch ($this->type)
		{
			case IMAGETYPE_GIF: return 'gif';
			case IMAGETYPE_PNG: return 'png';
			case IMAGETYPE_JPEG: return 'jpeg';
		}
	}
	
	public function mimetype()
	/*
		Returns the mime type of the image.
	*/
	{
		return $this->mimetype;
	}
	
	// ================
	// = Manipulation =
	// ================
	
	const left   = 0.0;
	const center = 0.5;
	const right  = 1.0;
	
	const top    = null;
	const middle = -0.5;
	const bottom = -1.0;
	
	protected $align_x = 0.5;
	protected $align_y = 0.5;
	
	protected $source;
	protected $source_width;
	protected $source_height;
	
	public function align($horizontal, $vertical)
	/*
		Sets the origin point along horizontal and vertical axis.
		
		0.0 - left or top;
		0.5 - center;
		1.0 - right or bottom.
	*/
	{
		if ($horizontal < 0 || is_null($horizontal))
		{
			$t = $vertical;
			$vertical = $horizontal;
			$horizontal = $t;
		}
		
		$horizontal = abs($horizontal);
		$vertical   = abs($vertical);
		
		$this->align_x = min(1, max(0, (float)$horizontal));
		$this->align_y = min(1, max(0, (float)$vertical));
		
		return $this;
	}
	
	public function crop($width, $height)
	/*
		Crops the image by a rectangle of specified dimensions.
	*/
	{
		$this->_load();
		list($width, $height) = $this->_dimensions($width, $height);
		
		$destination = imagecreatetruecolor($width, $height);
		
		if ($this->type === IMAGETYPE_PNG) 
		{
			imagealphablending($destination, false);
			imagesavealpha($destination, true);
		}
		
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
			throw new aeImageException('Failed to crop the image.');
		}
		
		return $this;
	}
	
	public function scale($width, $height)
	/*
		Scales the image to specified dimensions.
	*/
	{
		$this->_load();
		list($width, $height) = $this->_dimensions($width, $height);
		
		$destination = imagecreatetruecolor($width, $height);
		
		if ($this->type === IMAGETYPE_PNG) 
		{
			imagealphablending($destination, false);
			imagesavealpha($destination, true);
		}
		
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
			throw new aeImageException('Failed to scale the image.');
		}
		
		return $this;
	}
	
	public function fill($width, $height)
	/*
		Scales and crops the image to fill the specified dimensions.
	*/
	{
		$this->_load();
		
		$target_ratio = $width / $height;
		$source_ratio = $this->source_width / $this->source_height;
		
		if ($target_ratio > $source_ratio)
		{
			$this->scale($width, null);
		}
		else 
		{
			$this->scale(null, $height);
		}
		
		return $this->crop($width, $height);
	}
	
	public function fit($width, $height)
	/*
		Scales the image so that it fits the specified dimensions.
	*/
	{
		$this->_load();
		
		$target_ratio = $width / $height;
		$source_ratio = $this->source_width / $this->source_height;
		
		if ($target_ratio > $source_ratio)
		{
			$this->scale(null, $height);
		}
		else 
		{
			$this->scale($width, null);
		}
		
		return $this;
	}
	
	protected function _dimensions($width, $height)
	{
		if ((empty($width) || $width < 0)
		&& (empty($height) || $height < 0))
		{
			trigger_error('At least one dimension must be greater than 0.', E_USER_ERROR);
		}
		
		if (empty($width) || $width < 0)
		{
			$width = $height * $this->source_width / $this->source_height;
		}
		elseif (empty($height) || $height < 0)
		{
			$height = $width * $this->source_height / $this->source_width;
		}
		
		return array(round($width), round($height));
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
			throw new aeImageException('Failed to load the image: ' . $this->path);
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
	
	// ===========
	// = Filters =
	// ===========
	
	public function apply()
	/*
		Applies a filter to the image
		
			$image->apply(IMG_FILTER_GRAYSCALE);
		
		Uses PHP's `imagefilter()` function.
	*/
	{
		$this->_load();
		
		$arguments = func_get_args();
		array_unshift($arguments, $this->source);
		
		if (false === call_user_func_array('imagefilter', $arguments))
		{
			throw new aeImageException('Could not apply filter.');
		}
		
		return $this;
	}
	
	public function blur()
	/*
		Blurs the image using the Gaussian method.
	*/
	{
		return $this->apply(IMG_FILTER_GAUSSIAN_BLUR);
	}
	
	public function brightness($value)
	/*
		Changes the brightness of the image.
	
		Brightness value must be bewteen -1.0 and +1.0.
	*/
	{
		return $this->apply(IMG_FILTER_BRIGHTNESS, round(min(1, max(-1, (float) $value)) * 255));
	}

	public function contast($value)
	/*
		Changes the contrast of the image.
	
		Constast value must be bewteen -1.0 and +1.0.
	*/
	{
		return $this->apply(IMG_FILTER_BRIGHTNESS, round(min(1, max(-1, (float) $value)) * 100));
	}
	
	public function colorize($red, $green, $blue, $alpha = 0)
	/*
		Shifts the value of each component.
		
		Values must be bewteen -1.0 and +1.0.
	*/
	{
		$red = round(min(1, max(-1, (float) $red)) * 255);
		$green = round(min(1, max(-1, (float) $green)) * 255);
		$blue = round(min(1, max(-1, (float) $blue)) * 255);
		$alpha = round(min(1, max(-1, (float) $alpha)) * 255);
		
		// var_dump($red, $green, $blue, $alpha);
		
		return $this->apply(IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);
	}
	
	public function grayscale()
	/*
		Converts the image into grayscale.
	*/
	{
		return $this->apply(IMG_FILTER_GRAYSCALE);
	}
	
	public function negate()
	/*
		Reverses all colors of the image.
	*/
	{
		return $this->apply(IMG_FILTER_NEGATE);
	}
	
	public function pixelate($size, $advanced = false)
	/*
		Applies pixelation effect to the image.
	*/
	{
		return $this->apply(IMG_FILTER_PIXELATE, $size, $advanced === true);
	}
	
	public function smooth($value)
	/*
		Makes the image smoother.
	
		Any float value is accepted.
	*/
	{
		return $this->apply(IMG_FILTER_SMOOTH, (float) $value);
	}
	
	
	// ==========
	// = Output =
	// ==========
	
	protected $headers = array();
	protected $quality = 75;
	protected $suffix;
	protected $prefix;
	
	public function quality($quality)
	/*
		Sets quality level of JPEG.
	*/
	{
		$this->quality = (int) $quality;
		
		return $this;
	}
	
	public function progressive($whether = true)
	/*
		Sets JPEG images as progressive.
	*/
	{
		$this->_load();
		
		imageinterlace($this->source, $whether && $this->type === IMAGETYPE_JPEG);
		
		return $this;
	}
	
	public function interlaced($whether = true)
	/*
		Sets PNG and GIF images as interlaced.
	*/
	{
		$this->_load();
		
		imageinterlace($this->source, $whether && $this->type !== IMAGETYPE_JPEG);
		
		return $this;
	}
	
	public function suffix($suffix)
	/*
		Sets the suffix of the new image name.
	*/
	{
		$this->suffix = $suffix;
		
		return $this;
	}
	
	public function prefix($prefix)
	/*
		Sets the prefix of the new image name.
	*/
	{
		$this->prefix = $prefix;
		
		return $this;
	}
	
	public function cache($minutes, $uri = null)
	/*
		Caches the image for specified number of minutes.
		
		If URI is specified, the image will be saved to server-side
		response cache.
	*/
	{
		$this->_load();
		list($path, $type) = $this->_path_type($uri);
		
		while(ob_get_level()) ob_end_clean();
		
		ob_start();
		
		switch ($type)
		{
			case IMAGETYPE_GIF:
				$mimetype = 'image/gif';
				imagegif($this->source);
				break;
			case IMAGETYPE_PNG:
				$mimetype = 'image/png';
				imagepng($this->source);
				break;
			case IMAGETYPE_JPEG:
				$mimetype = 'image/jpeg';
				imagejpeg($this->source, null, $this->quality);
				break;
		}
		
		$this->headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $minutes * 60) . ' GMT';
		$this->headers['Cache-Control'] = !is_null($uri) ? 'public' : 'private';
		
		if (!is_null($uri))
		{
			$cache = new aeResponseCache();
			
			$cache
				->duration($minutes)
				->headers(array_merge($this->headers, array(
					'Content-Type' => $mimetype
				)))
				->content(ob_get_clean())
				->save($uri);
		}
		
		return $this;
	}
	
	public function save($path = null)
	/*
		Saves the image.
		
		If no path is specified, the existing file will be overwritten, unless
		`prefix()` or `suffix()` is set. If only file name is specified, it 
		will be saved into the same directory.
	*/
	{
		$this->_load();
		list($path, $type) = $this->_path_type($path);
		
		switch ($type)
		{
			case IMAGETYPE_GIF:
				$success = imagegif($this->source, $path);
				break;
			case IMAGETYPE_PNG:
				$success = imagepng($this->source, $path);
				break;
			case IMAGETYPE_JPEG:
				$success = imagejpeg($this->source, $path, $this->quality);
				break;
		}
		
		if (!$success) 
		{
			throw new aeImageException('Failed to save the image: ' . $path);
		}
		
		$this->_unload();
		
		$this->quality = 75;
		$this->prefix = null;
		$this->suffix = null;
		
		return new aeImage($path);
	}

	public function dispatch($name = null)
	/*
		Dispatches the image as an HTTP response.
	*/
	{
		$this->_load();
		list($path, $type) = $this->_path_type($name);
		
		while(ob_get_level()) ob_end_clean();
		
		foreach ($this->headers as $key => $value) 
		{
			header($key . ': ' . $value);
		}
		
		switch ($type)
		{
			case IMAGETYPE_GIF:
				header('Content-Type: image/gif');
				imagegif($this->source);
				exit;
			case IMAGETYPE_PNG:
				header('Content-Type: image/png');
				imagepng($this->source);
				exit;
			case IMAGETYPE_JPEG:
				header('Content-Type: image/jpeg');
				imagejpeg($this->source, null, $this->quality);
				exit;
		}
	}
	
	protected function _path_type($path)
	{
		$type = $this->type;
		$parts = pathinfo($this->path);
		
		if (!empty($path))
		{
			$_parts = pathinfo($path);
			
			$path = ($_parts['dirname'] === '.'
				? $parts['dirname'] : $_parts['dirname'])
				. '/' . $_parts['basename'];
			
			switch ($_parts['extension']) 
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
		
		return array($path, $type);
	}
}

class aeImageException extends Exception {}