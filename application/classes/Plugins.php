<?php

class Plugins {

	static $plugins = array();

	public static function register($hook, $callback)
	{
		if ( ! isset(self::$plugins[$hook]))
		{
			self::$plugins[$hook] = array();
		}
		self::$plugins[$hook][] = $callback;
	}

	public function call($hook)
	{
		if (!isset(self::$plugins[$hook]))
		{
			return FALSE;
		}
		foreach (self::$plugins[$hook] as $plugin)
		{
			$args = func_get_args();
			unset($args[0]);
			call_user_func_array($plugin, $args);
		}
	}

}
