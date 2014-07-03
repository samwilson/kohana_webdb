<?php

date_default_timezone_set('Australia/Perth');
# define('KOHANA_COOKIE_SALT', 'Enter random string here');
define('KOHANA_LANG', 'en');
#define('KOHANA_BASE_URL', '/kohana_webdb/');
define('KOHANA_ENVIRONMENT', 'production');
#define('KOHANA_LOCALE', 'en_US.utf-8')

$modules = array(

	// Entity Relationship Diagrams:
	#__DIR__.'/modules/kohana_webdb_erd',

	// DB Auth:
	#__DIR__.'/modules/kohana_dbauth',
	#__DIR__.'/modules/kohana_webdb_dbauth',

);
