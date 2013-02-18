<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * This (with the exception of the 'DB' bit that replaces the earlier 'LDAP'
 * bit) comes from Beau Dacious' LDAP driver.
 *
 * @package   WebDB
 * @category  Auth
 * @author    Beau Dacious <dacious.beau@gmail.com>
 * @copyright (c) 2009 Beau Dacious
 * @link      http://github.com/nocash/KadLDAP
 * @author    Sam Wilson
 * @license   http://www.opensource.org/licenses/mit-license.php
 * @link      http://github.com/samwilson/kohana_webdb
 */
abstract class Webdb_Auth extends Auth
{

	/**
	 * Login method override for Auth module.
	 *
	 * The Auth module salts all passwords before passing them around. This is
	 * no good if we're working with LDAP.
	 *
	 * @param string $username
	 * @param string $password
	 * @param boolean $remember
	 * @return boolean
	 */
	public function login($username, $password, $remember = FALSE)
	{
		if (empty($password))
		{
			return FALSE;
		}
		if (strtoupper($this->_config['driver']) == 'DB')
		{
			return $this->_login($username, $password, $remember);
		}
		else
		{
			return parent::login($username, $password, $remember);
		}
	}

}
