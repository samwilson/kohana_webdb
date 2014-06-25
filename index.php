<?php

if ( ! file_exists('config.php'))
{
	echo 'Configuration file not found. Please copy config.dist.php to config.php and edit the values therein.';
	exit(1);
}
require 'config.php';

define('EXT', '.php');
define('DOCROOT', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

/*
 * Path to application data
 */
if ( ! defined('APPPATH'))
{
	define('APPPATH', DOCROOT.'application'.DIRECTORY_SEPARATOR);
}
if ( ! is_dir(APPPATH))
{
	// Create directory, after the precedent of Kohana_Core::init();
	mkdir(APPPATH, 0755, TRUE) OR die('Unable to make directory '.APPPATH);
	chmod(APPPATH, 0755);
}

define('MODPATH', DOCROOT.'modules'.DIRECTORY_SEPARATOR);
define('SYSPATH', DOCROOT.'vendor'.DIRECTORY_SEPARATOR.'kohana'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR);
if ( ! defined('KOHANA_BASE_URL')) define('KOHANA_BASE_URL', '/kohana_webdb/');
if (substr(KOHANA_BASE_URL, -1) != '/') 
{
	echo 'KOHANA_BASE_URL must have trailing slash';
	exit(1);
}
if ( ! defined('KOHANA_ENVIRONMENT')) define('KOHANA_ENVIRONMENT', 'production');
if ( ! defined('KOHANA_COOKIE_SALT') OR KOHANA_COOKIE_SALT=='')
{
	echo 'Please define KOHANA_COOKIE_SALT in config.php';
	exit(1);
}

/*
 * Set locale and language
 */
if (!defined('KOHANA_LANG')) define('KOHANA_LANG', 'en-au');
if (!defined('KOHANA_LOCALE')) define('KOHANA_LOCALE', 'en_US.utf-8');
setlocale(LC_ALL, KOHANA_LOCALE);

/*
 * Load and configure Kohana Core
 */
if (!defined('KOHANA_START_TIME')) define('KOHANA_START_TIME', microtime(TRUE));
if (!defined('KOHANA_START_MEMORY')) define('KOHANA_START_MEMORY', memory_get_usage());
require SYSPATH . 'classes/Kohana/Core'.EXT;
require SYSPATH . 'classes/Kohana'.EXT;
require DOCROOT . 'vendor/autoload.php';
spl_autoload_register(array('Kohana', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');
I18n::lang(KOHANA_LANG);
Kohana::$environment = constant('Kohana::'.strtoupper(KOHANA_ENVIRONMENT));

/*
 * Try to create log directory.
 */
$cache_dir = APPPATH.'cache';
if ( ! file_exists($cache_dir))
{
	// Create directory, after the precedent of Kohana_Core::init();
	mkdir($cache_dir, 0755, TRUE) OR die('Unable to make directory '.$cache_dir);
	chmod($cache_dir, 0755);
}

/**
 * Shutdown for CLI, can be removed when http://dev.kohanaframework.org/issues/4537 is resolved.
 */
if (PHP_SAPI == 'cli')
{
	register_shutdown_function(function()
	{
		if (Kohana::$errors AND $error = error_get_last() AND in_array($error['type'], Kohana::$shutdown_errors))
		{
			exit(1);
		}
		
	});
}

/*
 * Kohana initialisation.
 */
Kohana::init(array(
	'base_url' => KOHANA_BASE_URL,
	'index_file' => FALSE,
	'cache_dir' => $cache_dir,
	'profile' => Kohana::$environment != Kohana::PRODUCTION,
	'errors' => Kohana::$environment != Kohana::PRODUCTION,
	'caching' => Kohana::$environment == Kohana::PRODUCTION,
));

/*
 * Try to create log directory.
 */
$log_dir = APPPATH.'logs';
if (!file_exists($log_dir))
{
	// Create directory, after the precedent of Kohana_Core::init();
	mkdir($log_dir, 0755, TRUE);
	chmod($log_dir, 0755);
}
Kohana::$log->attach(new Log_File($log_dir));
unset($log_dir);

/*
 * 
 */
Kohana::$config->attach(new Config_File);

Cookie::$salt = KOHANA_COOKIE_SALT;

/*
 * Load all required modules.
 */
$required_modules = array(
	'auth'        => MODPATH.'auth',
	'cache'       => MODPATH.'cache',
	'database'    => MODPATH.'database',
	'pagination'  => MODPATH.'pagination',
	'minion'      => MODPATH.'minion',
	'tasks-cache' => DOCROOT.'vendor/kohana-minion/tasks-cache',
	'dbauth'      => MODPATH.'kohana_dbauth',
	'kadldap'     => MODPATH.'kohana_kadldap',
);
foreach ($modules as $mod)
{
	if (is_dir($mod))
	{
		$required_modules[basename ($mod)] = $mod;
	}
}
Kohana::modules($required_modules);
unset($modules, $required_modules);

/**
 * Routes.
 */
Route::set('login', 'login')->defaults(array(
	'controller' => 'User',
	'action' => 'login',
));
Route::set('logout', 'logout')->defaults(array(
	'controller' => 'User',
	'action' => 'logout',
));
Route::set('profile', 'profile')->defaults(array(
	'controller'=>'User',
	'action' => 'profile',
));
Route::set('default', '(<action>(/<dbname>(/<tablename>(/<id>))))')
	->defaults(array(
		'controller' => 'WebDB',
		'action' => 'index',
		'dbname' => NULL,
		'tablename' => NULL,
		'id' => NULL
));

/**
 * Site title is defined after config and modules have had a chance at at.
 */
if (!defined('SITE_TITLE')) define('SITE_TITLE', 'WebDB');

/**
 * Execute the request.
 */
if (PHP_SAPI == 'cli')
{
	/**
	 * Include the Unit Test module and leave the rest to PHPunit.
	 */
	if (substr(basename($_SERVER['PHP_SELF']), 0, 7) == 'phpunit')
	{
		// Disable output buffering
		if (($ob_len = ob_get_length()) !== FALSE)
		{
			// flush_end on an empty buffer causes headers to be sent. Only flush if needed.
			if ($ob_len > 0) ob_end_flush();
			else ob_end_clean();
		}
		Kohana::modules(Kohana::modules() + array('unittest' => MODPATH.'unittest'));
		//Database::$default = 'testing';
		return; // Execution will be continued by phpunit
	}

	/*
	 * Execute minion if this is a command line request.
	 */
	set_exception_handler(array('Minion_Exception', 'handler'));
	Minion_Task::factory(Minion_CLI::options())->execute();
}
else
{
	/*
	 * Otherwise, execute the main request.
	 */
	echo Request::factory(TRUE, array(), FALSE)
		->execute()
		->send_headers(TRUE)
		->body();
}
