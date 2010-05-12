<?php defined('SYSPATH') or die('No direct script access.');

// Static resource file serving (CSS, JS, images)
Route::set('webdb/resources', 'webdb/resources(/<file>)', array('file' => '.+'))
	->defaults(array(
	'controller' => 'webdb',
	'action'     => 'resources',
	'file'       => NULL,
));

// Main WebDB URL structure
Route::set('webdb', 'webdb/(<action>(/<dbname>(/<tablename>(/<rowid>))))')
	->defaults(array(
	'controller' => 'webdb',
	'action'     => 'index',
	'dbname'     => NULL,
	'tablename'  => NULL,
	'rowid'      => NULL
));
