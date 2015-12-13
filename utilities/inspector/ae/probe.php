<?php

#
# Copyright 2011-2015 Anton Muraviev <anton@goodmoaning.me>
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

\ae::invoke('\ae\Probe');

class Probe
/*
	Allows you to monitor memory and execution time.
*/
{
	protected $name;
	
	public function __construct($name)
	{
		if (is_null($name))
		{
			trigger_error("Probe must have a name.", E_USER_WARNING);
		}
		
		$this->name = $name;
	}
	
	public function mark($description = null)
	/*
		Triggers a notice with performance mertrics in both absolute and relative terms.
	*/
	{
		static $initial_time, $initial_memory, $last_t, $last_m;
		
		$t = microtime(true);
		$m = memory_get_usage();

		if (!isset($initial_time))
		{
			$last_t = $initial_time = $t;
			$last_m = $initial_memory = $m;
		} 
			
		$dt = $t - $last_t;
		$dm = $m - $last_m;
		$ts = $t - $initial_time;
		$ms = $m - $initial_memory;
		
		$last_t = $t;
		$last_m = $m;
		
		if (!is_null($description))
		{
			$notice = sprintf('%s: %s. Timestamp: %.0fms (+%.3fms). Footprint: %s (%s).', 
				$this->name, $description, 
				$ts * 1000, $dt * 1000, 
				self::_format($ms, 0), 
				($dm < 0 ? '-' : '+') . self::_format(abs($dm), 0));
			
			\ae::log($notice);
		}
		
		return $this;
	}
	
	private static function _format($bytes, $precision = 2)
	{ 
		$units = ['b', 'kb', 'mb', 'gb', 'tb']; 
	
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 
		
		$bytes /= pow(1024, $pow);
	
		return round($bytes, $precision) . $units[$pow]; 
	} 
}
