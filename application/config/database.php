<?php

global $database_config;
return array(
	'default' => array(
		'type' => 'MySQL',
		'connection' => $database_config,
		'table_prefix' => '',
		'charset' => 'utf8',
		'caching' => FALSE,
	),
);
