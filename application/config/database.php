<?php

global $database_config;
if ( ! is_array($database_config))
{
	throw new Exception("Unable to load database config.");
}
return array(
	'default' => array(
		'type' => 'MySQLi',
		'connection' => $database_config,
		'table_prefix' => '',
		'charset' => 'utf8',
		'caching' => FALSE,
	),
);
