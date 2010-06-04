<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Database Auth driver to authenticate as a database user.
 *
 * @package  WebDB
 * @category Auth
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_Auth_Db extends Webdb_Auth
{

	private $_db_password_session_suffix = '_dbpass';

	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @param void   $remember NOT USED
	 */
	protected function _login($username, $password, $remember = NULL)
	{
		try
		{
			$this->_session->set($this->_config['session_key'], $username);
			$this->_session->set($this->_config['session_key'].$this->_db_password_session_suffix, $password);
			$db = new Webdb_DBMS;
			return $this->complete_login($username);
		} catch (Webdb_DBMS_ConnectionException $e)
		{
			$this->_session->delete($this->_config['session_key']);
			$this->_session->delete($this->_config['session_key'].$this->_db_password_session_suffix);
			throw $e;
		}
		/*
		$config = kohana::config('database')->default;
		$config['connection']['password'] = $password;
		$config['connection']['username'] = $username;
		unset(Database::$instances['default']);
		//exit(__FILE__.__LINE__.kohana::debug($config));
		$db = Database::instance('default', $config);
		try
		{
			$db->connect();
			$this->_session->set($this->_config['session_key'].$this->_db_password_session_suffix, $password);
			return $this->complete_login($username);
		} catch (Exception $e)
		{
			throw new Kohana_Exception('Unable to connect to DBMS.');
			//return FALSE;
		}
		 *
		*/

	}

	public function password($username)
	{
		return $this->_session->get($this->_config['session_key'].$this->_db_password_session_suffix);
	}

	public function check_password($password)
	{

	}

}