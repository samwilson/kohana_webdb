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
		$this->connect();
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
	public function connect($dbname = NULL)
	{
		$this->_config = kohana::config('database');
		// Use DB-specific config if it's there.
		if ($dbname != NULL && isset($this->_config->$dbname))
		{
			$this->_config = $this->_config->$dbname;
			$config_group_name = $dbname;
		} else // Otherwise go with the default.

		{
			$this->_config = $this->_config->default;
			$config_group_name = 'default';
		}
		if ($dbname != NULL)
		{
			$this->_config['connection']['database'] = $dbname;
		}
		try
		{
			// Try to connect with the Database config credentials.
			Kohana::$log->add(Kohana::DEBUG, "Connecting with database config group $config_group_name:");
			Kohana::$log->add(Kohana::DEBUG, "    database = ".$this->_config['connection']['database']);
			Kohana::$log->add(Kohana::DEBUG, "    username = ".$this->_config['connection']['username']);
			unset(Database::$instances[$config_group_name]);
			$this->_db = Database::instance($config_group_name, $this->_config);
			$this->_db->connect();

		} catch (Exception $e)
		{
			// If that fails, try with those from Auth config.
			Kohana::$log->add(Kohana::INFO, "Unable to connect; trying Auth.");
			$username = auth::instance()->get_user();
			$this->_config['connection']['username'] = $username;
			$password = auth::instance()->password($username);
			$this->_config['connection']['password'] = $password;
			unset(Database::$instances[$config_group_name]);
			try
			{
				Kohana::$log->add(Kohana::DEBUG, "Connecting with database config group $config_group_name:");
				Kohana::$log->add(Kohana::DEBUG, "    database = ".$this->_config['connection']['database']);
				Kohana::$log->add(Kohana::DEBUG, "    username = ".$this->_config['connection']['username']);
				$this->_db = Database::instance($config_group_name, $this->_config);
				$this->_db->connect();
			} catch (Exception $e)
			{
				// Second connection failure: give up.
				Kohana::$log->add(Kohana::DEBUG, "Unable to connect; giving up.");
				throw new Webdb_DBMS_ConnectionException('Unable to connect to DBMS.');
			}
			Kohana::$log->add(Kohana::INFO, 'Connecting with Auth credentials successful.');
			return;
		}
		Kohana::$log->add(Kohana::INFO, 'Connecting with database config file credentials successful.');
		return;
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
			$sql = $this->_get_list_db_statement();
			$query = $this->_db->query(Database::SELECT, $sql, true);
			foreach ($query as $row)
			{
				$this->_database_names[] = current($row);
			}
		}
		return $this->_database_names;
	}

	/**
	 * Different DBMSs have different ways of listing available databases.  This
	 * method returns the correct SQL for the current DBMS.
	 * With kudos to Zibikoss on the Kohana forums.
	 *
	 * @link http://forum.kohanaframework.org/comments.php?DiscussionID=1965
	 */
	private function _get_list_db_statement()
	{
		// For mysql, mysqli and pdosqlite:
		return 'SHOW DATABASES';

		// For pgsql
		$sql = 'select datname from pg_database';

		// For mssql and sybase
		$sql = 'sp_helpdb';

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
			$dbname = Request::instance()->param('dbname', FALSE);
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
		$this->connect($dbname);
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
		$config = kohana::config('webdb')->permissions;
		if (empty($config['table']))
		{
			return array(array(
				'database_name' => '*',
				'table_name'    => '*',
				'column_names'  => NULL,
				'where_clause'  => NULL,
				'permission'    => '*',
				'identifier'    => '*',
			));
		}
		$query = new Database_Query_Builder_Select();
		$query->from($config['database'].'.'.$config['table']);
		$query->where('identifier', 'IN', array(Auth::instance()->get_user(), '*'));
		$rows = $query->execute($this->_db)->as_array();
		return $rows;
	}

}
