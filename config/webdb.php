<?php defined('SYSPATH') or die('No direct access allowed.');

return array(
	/*
	 * Where to find WebDB's permissions table.  Set 'table' to FALSE if you
	 * do not wish to use this feature; it will be assumed that every logged-in
	 * user has full edit permissions (i.e. this is probably what you want when
	 * using WebDB's 'DB' Auth driver).
	 */
	'permissions' => array(
		'database' => 'auth',
		'table' => 'permissions',
	),
);