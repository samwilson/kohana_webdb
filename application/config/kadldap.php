<?php

return array(
	'kadldap' => array(
		'domain_controllers' => array(WebDB::config('ldap_domain_controller')),
		'account_suffix'     => WebDB::config('ldap_account_suffix'),
		'base_dn'            => WebDB::config('ldap_base_dn'),
		'ad_username'        => WebDB::config('ldap_ad_username'),
		'ad_password'        => WebDB::config('ldap_ad_password'),
	)
);
