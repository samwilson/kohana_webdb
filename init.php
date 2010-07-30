<?php defined('SYSPATH') or die('No direct script access.');

// Serve static media files (CSS, JS, images; these aren't all 'media', but
// it's the Kohana convention to group them so).
Route::set('webdb/media', 'webdb/media(/<file>)', array('file' => '.+'))
	->defaults(array(
	'controller' => 'webdb',
	'action'     => 'media',
	'file'       => NULL,
));

// Main WebDB URL structure
Route::set('webdb', 'webdb(/<action>(/<dbname>(/<tablename>(/<id>))))')
	->defaults(array(
	'controller' => 'webdb',
	'action'     => 'index',
	'dbname'     => NULL,
	'tablename'  => NULL,
	'id'      => NULL
));
