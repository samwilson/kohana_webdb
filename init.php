<?php defined('SYSPATH') or die('No direct script access.');

// Static file serving (CSS, JS, images)
Route::set('webdb/resources', 'webdb/resources(/<file>)', array('file' => '.+'))
	->defaults(array(
		'controller' => 'webdb',
		'action'     => 'resources',
		'file'       => NULL,
	));

Route::set('webdb', 'webdb/(<action>(/<dbname>(/<table>)))')
	->defaults(array(
		'controller' => 'webdb',
		'action'     => 'index',
		'dbname'     => NULL,
		'tablename'  => NULL
	));
