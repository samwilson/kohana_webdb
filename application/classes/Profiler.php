<?php

class Profiler extends Kohana_Profiler {

	/**
	 * Starts a new benchmark and returns a unique token. The returned token
	 * _must_ be used when stopping the benchmark.
	 * 
	 * The same as parent::start, but takes into account Kohana::$profiling
	 *
	 *     $token = Profiler::start('WebDB', __METHOD__);
	 *
	 * @param   string  $group  group name
	 * @param   string  $name   benchmark name
	 * @return  string
	 */
	public static function start($group, $name)
	{
		if (Kohana::$profiling)
		{
			return parent::start($group, $name);
		}
	}

}
