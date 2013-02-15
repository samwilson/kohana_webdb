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
class Webdb_Auth_DB extends Auth
{

	/** @var string The key under which the password is stored. */
	private $_db_password_session_key;

	/**
	 * Loads Session and configuration options.
	 *
	 * @param   array  $config  Config Options
	 * @return  void
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->_db_password_session_key = $this->_config['session_key'].'_dbpass';
	}

	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @param void   $remember NOT USED
	 */
	protected function _login($username, $password, $remember)
	{
		$config = Kohana::$config->load('database')->default;
		$server = $config['connection']['hostname'];
		$connected = @mysql_connect($server, $username, $password);
		if ($connected)
		{
			// Save password
			$this->_session->set($this->_db_password_session_key, $password);
			// Finish loggin in
			return $this->complete_login($username);
		}
		// Log in failed
		return FALSE;
	}

	/**
	 * Get the password for the currently logged in user.
	 * 
	 * @param string $username NOT USED
	 * @return null|string The password, or NULL if the user has not logged in.
	 */
	public function password($username)
	{
		return $this->_session->get($this->_db_password_session_key);
	}

	public function check_password($password)
	{
		
	}

}