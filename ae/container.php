<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2016 Anton Muraviev <anton@goodmoaning.me>
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
	Wraps output of a script into another script and helps you avoid
	the mess that using separate header / footer scripts usually entails.
	
	Example of container.php:
	
		<html>
		<head>
			<title><?= $title ?></title>
		</head>
		<body>
			<?= $content ?>
		</body>
		</html>
		
	Example of content.php:
	
		<?php 
		$container = ae::container('container.php')
			->set('title', 'Container example');
		?>
		<h1>Hello World!</h1>
		
	When rendered the content.php will produce:
	
		<html>
		<head>
			<title>Container example</title>
		</head>
		<body>
			<h1>Hello World!</h1>
		</body>
		</html>
*/
{
	protected $path;
	protected $buffer;
	protected $context;
	protected static $vars = array();
	
	public function __construct($path)
	{
		$this->path = $path;
		$this->buffer = new aeBuffer();
		$this->context = new aeSwitch(self::$vars, self::$vars);
	}
	
	public function __destruct()
	{
		self::$vars['content'] = $this->buffer->render();
		
		ae::output($this->path, self::$vars);
	}
	
	public function set($name, $value)
	/*
		Sets a container variable.
	*/
	{
		self::$vars[$name] = $value;
		
		return $this;
	}
}