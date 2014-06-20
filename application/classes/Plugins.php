<?php

class Plugins {

	static $plugins = array();

	/**
	 * Register a plugin with a given hook.
	 *
	 * @param string $hook
	 * @param callable $callback
	 */
	public static function register($hook, $callback)
	{
		if ( ! isset(self::$plugins[$hook]))
		{
			self::$plugins[$hook] = array();
		}
		self::$plugins[$hook][] = $callback;
	}

	/**
	 * Call all plugins that have hooked to a given hook.
	 *
	 * @param string $hook
	 * @return boolean FALSE if the hook not found, TRUE otherwise
	 * @throws HTTP_Exception 500 on any exception from the called plugins.
	 */
	public static function call($hook)
	{
		if ( ! isset(self::$plugins[$hook]))
		{
			return FALSE;
		}
		foreach (self::$plugins[$hook] as $callback)
		{
			// Get args, removing the first (the hook name)
			$args = func_get_args();
			array_shift($args);

			// Copy all args to a new array
			$param_arr = array();
			foreach ($args as $k => &$a)
			{
				$param_arr[$k] = &$a;
			}

			// Attempty to call the plugin
			try
			{
				call_user_func_array($callback, $param_arr);
			} catch (Exception $e)
			{
				$msg = 'Error calling :callback for plugin hook :hook';
				$vars = array(':callback' => $callback, ':hook' => $hook);
				throw HTTP_Exception::factory(500, $msg, $vars, $e);
			}
		}
	}

}
