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

ae::invoke('aeProbe');

class aeProbe
{
	const report_correction = 92; // footprint of the report() itself
	const object_correction = 448; // foot of the probe itself
	
	protected $name;
	
	public function __construct($name)
	{
		if (is_null($name))
		{
			trigger_error("Probe must have a name.", E_USER_ERROR);
		}
		
		$this->name = $name;
		$this->report('started');
	}
	
	public function __destruct()
	{
		$this->report('finished');
	}
	
	public function report($description)
	{
		static $initial_time, $last_t, $last_m;
		
		$t = microtime(true);
		$m = memory_get_usage() - self::report_correction;
		$ts = 0.0;

		if (!isset($initial_time))
		{
			$dt = 0;
			$dm = 0;
			$initial_time = $t;

			$last_t = $t;
			$last_m = $m - self::object_correction;
		}
		else
		{
			$dt = $t - $last_t;
			$dm = $m - $last_m;
			$ts = $t - $initial_time;
			
			$last_t = $t;
			$last_m = $m;
		}
		
		$notice = '%s %s. Timestamp: %.0fms (+%.3fms). Footprint: %d bytes (%+d bytes).';
		
		trigger_error(sprintf($notice, $this->name, $description, $ts * 1000, $dt * 1000, $m, $dm), E_USER_NOTICE);
	}
}


?>