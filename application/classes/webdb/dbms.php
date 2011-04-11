<?php defined('SYSPATH') or die('No direct script access.');
/**
 * This class represents an instance of the DBMS.  With it, we connect
 * and query, and retrieve [Webdb_DBMS_Database] objects.
 *
 * @package  WebDB
 * @category DBMS
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_DBMS
{

	/**
	 * @var array[string] Names of all available databases (except
	 * 'information_schema').
	 */
	private $_database_names;

	/** @var Database */
	private $_db;

	/** @var Config */
	private $_config;

	/** @var boolean */
	public $logged_in = FALSE;

	/**
	 */
	public function __construct()
	{
	}

	/**
	 * Connect to the DBMS.
	 *
	 *  1. If no `$dbname` is specified, try to connect to the database named in
	 *     the [Database] config file with the credentials from the same.  If this
	 *     doesn't work, try connecting to the same database with credentials
	 *     garnered from the [Auth] instance.  If this doesn't work (or,
	 *     obviously, if there is no Auth) throw an exception.
	 *  2. When a `$dbname` is provided, repeat the above, but ignore any
	 *     database name given in the config file.
	 *
	 * Also, if a database name is provided, then a group of the same name is
	 * used if found in the Database config file.
	 *
	 * @param string $dbname The name of the database to which to connect.
	 * @return void
	 * @throws Webdb_DBMS_ConnectionException if unable to connect.
	 */
	public function connect()
	{
		$this->_config = Kohana::config('database')->default;
		$this->_connection = mysql_connect(
			$this->_config['connection']['hostname'],
			$this->username(),
			$this->password()
		);
		return $this->_connection;
	}

	/**
	 * Get a list of all databases visible to the current database user.  The
	 * 'information_schema' is omitted.
	 *
	 * @return array[string] List of all available databases.
	 */
	public function list_dbs()
	{
		if (!is_array($this->_database_names))
		{
			$this->_database_names = array();
			$query = mysql_query("SHOW DATABASES", $this->_connection);
			while ($row = mysql_fetch_row($query))
			{
				$this->_database_names[] = $row[0];
			}
		}
		return $this->_database_names;
	}

	/**
	 * Get or set password, including saving it in Session.
	 *
	 * @param string $password
	 * @return void|string Nothing if setting; the username if getting.
	 */
	public function password($password = FALSE)
	{
		if ($password !== FALSE)
		{
			$this->_config['connection']['password'] = $password;
			Session::instance()->set('password', $password);
		} else
		{
			if (Session::instance()->get('password', FALSE))
			{
				$this->_config['connection']['password'] = Session::instance()->get('password');
			}
			return $this->_config['connection']['password'];
		}
	}

	/**
	 * Get or set username, including saving it in Session.
	 * 
	 * @param string $username
	 * @return void|string Nothing if setting; the username if getting.
	 */
	public function username($username = FALSE)
	{
		if ($username)
		{
			$this->_config['connection']['username'] = $username;
			Session::instance()->set('username', $username);
		} else
		{
			if (Session::instance()->get('username', FALSE))
			{
				$this->_config['connection']['username'] = Session::instance()->get('username');
			}
			return $this->_config['connection']['username'];
		}
	}

	/**
	 * Get a database object.  If no name is given, try to determine the current
	 * database from the 'dbname' route variable.  If that fails, return false.
	 *
	 * @param string $dbname The name of the desired database.
	 * @return Webdb_DBMS_Database The database object.
	 * @return false If no database could be found.
	 * @throws Exception
	 */
	public function get_database($dbname = FALSE)
	{
		if (!$dbname)
		{
			$dbname = Request::current()->param('dbname', FALSE);
			if ($dbname)
			{
				return $this->get_database($dbname);
			} else
			{
				return false;
			}
		}
		if (!in_array($dbname, $this->list_dbs()))
		{
			throw new Exception("The database '$dbname' could not be found.");
		}
		$this->_config['connection']['database'] = $dbname;
		$this->_db = Database::instance(NULL, $this->_config);
		$this->_db->connect();
		return new Webdb_DBMS_Database($this, $dbname);
	}

	public function get_database_driver()
	{
		return $this->_db;
	}

	/**
	 * Get an array of permissions for the current user.
	 *
	 * @return array
	 */
	public function get_permissions()
	{
		$default_permissions = array(array(
			'database_name' => '*',
			'table_name'    => '*',
			'column_names'  => '*',
			'where_clause'  => NULL,
			'permission'    => '*',
			'identifier'    => '*',
		));
		$config = Kohana::config('webdb');
		if (!isset($config->permissions) || empty($config->permissions['table']))
		{
			return $default_permissions;
		}
		$query = new Database_Query_Builder_Select();
		$query->from($config['database'].'.'.$config['table']);
		$query->where('identifier', 'IN', array(Auth::instance()->get_user(), '*'));
		$rows = $query->execute($this->_db)->as_array();
		return $rows;
	}

}
