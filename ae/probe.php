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

// TODO: Improved memory and time presentation in inspector.
// TODO: Memory footprint should be more readable (b, kb, mb, etc.)
// FIXME: Object and method footprints must be dynamically calculated.

ae::invoke('aeProbe');

class aeProbe
/*
	A very simple profiler.
*/
{
	const report_footprint = 96; // footprint of the report()
	const object_footprint = 448; // footprint of the aeProbe instance
	
	protected $name;
	
	public function __construct($name)
	{
		if (is_null($name))
		{
			trigger_error("Probe must have a name.", E_USER_WARNING);
		}
		
		$this->name = $name;
		$this->report('started');
	}
	
	public function __destruct()
	{
		$this->report('finished');
	}
	
	public function report($description)
	/*
		Triggers a notice with performance mertrics in both absolute and delta terms.
	*/
	{
		static $initial_time, $last_t, $last_m;
		
		$t = microtime(true);
		$m = memory_get_usage() - self::report_footprint;
		$ts = 0.0;

		if (!isset($initial_time))
		{
			$dt = 0;
			$dm = 0;
			$initial_time = $t;

			$last_t = $t;
			$last_m = $m - self::object_footprint;
		}
		else
		{
			$dt = $t - $last_t;
			$dm = $m - $last_m;
			$ts = $t - $initial_time;
			
			$last_t = $t;
			$last_m = $m;
		}
		
		$notice = sprintf('%s %s. Timestamp: %.0fms (+%.3fms). Footprint: %s (%s).', 
			$this->name, $description, 
			$ts * 1000, $dt * 1000, 
			self::_format($m, 0), 
			($dm < 0 ? '-' : '+') . self::_format(abs($dm), 0));
		
		ae::log($notice);
	}
	
	private static function _format($bytes, $precision = 2)
	{ 
		$units = array('b', 'kb', 'mb', 'gb', 'tb'); 
	
		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 
		
		$bytes /= pow(1024, $pow);
	
		return round($bytes, $precision) . $units[$pow]; 
	} 
}


?>