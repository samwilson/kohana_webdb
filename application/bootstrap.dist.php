<?php defined('SYSPATH') or die('No direct script access.');

// -- Environment setup --------------------------------------------------------
// Load the core Kohana class
require SYSPATH.'classes/Kohana/Core'.EXT;
// Load empty core extension
require SYSPATH.'classes/Kohana'.EXT;

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('Australia/Perth');

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'en_AU.utf-8');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
I18n::lang('en-us');

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
if (isset($_SERVER['KOHANA_ENV']))
{
	Kohana::$environment = constant('Kohana::'.strtoupper($_SERVER['KOHANA_ENV']));
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string base_url path, and optionally domain, of your application NULL
 * - string index_file name of your index file, usually "index.php" index.php
 * - string charset internal character set used for input and output utf-8
 * - string cache_dir set the internal cache directory APPPATH/cache
 * - integer cache_life lifetime, in seconds, of items cached 60
 * - boolean errors enable or disable error handling TRUE
 * - boolean profile enable or disable internal profiling TRUE
 * - boolean caching enable or disable internal caching FALSE
 * - boolean expose set the X-Powered-By header FALSE
 */
Kohana::init(array(
	'base_url' => dirname($_SERVER['SCRIPT_NAME']),
	'index_file' => FALSE,
));

Cookie::$salt = 'change this salt';
/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	'cache'      => MODPATH.'cache',
	'database'   => MODPATH.'database',
	'pagination' => MODPATH.'pagination',
	//'kadldap'    => MODPATH.'kadldap',
	'auth'       => MODPATH.'auth',
));

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
// Main WebDB URL structure
Route::set('default', '(<action>(/<dbname>(/<tablename>(/<id>))))')
	->defaults(array(
		'controller' => 'WebDB',
		'action' => 'index',
		'dbname' => NULL,
		'tablename' => NULL,
		'id' => NULL
	));
