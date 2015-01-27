<?php

class WebDB {

	/**
	 * Current WebDB version. Conforms to the Semantic Versioning standard.
	 *
	 * @link http://semver.org/ Official website
	 * @const
	 */
	const VERSION = '6.1.0';

	/**
	 * Get a localized message.
	 *
	 * @param string $str The message name.
	 * @return string
	 */
	public static function msg($str)
	{
		return __(Kohana::message('webdb', $str));
	}

	public static function config($name)
	{
		try
		{
			$setting = DB::select('value')
				->from('settings')
				->where('name', '=', $name)
				->execute()
				->current();
		} catch (Exception $e)
		{
			$setting = FALSE;
		}
		if ( ! $setting)
		{
			return Kohana::$config->load('webdb')->get($name);
		}
		return Arr::get($setting, 'value');
	}

}
