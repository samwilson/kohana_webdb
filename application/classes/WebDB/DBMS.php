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
	
	/** @var resource MySQL link identifier */
	private $_connection;

	/** @var Config */
	private $_config;

	/** @var boolean */
	public $logged_in = FALSE;

	/**
	 * Construct the DBMS object, including loading database configuration.
	 */
	public function __construct()
	{
		$this->_config = Kohana::$config->load('database')->default;
	}

	/**
	 * Connect to the DBMS.
	 *
	 *  1. Try to connect to the database host named in the [Database] config
	 *     file with the credentials from same.  If this doesn't work, try
	 *     connecting to the same place with credentials garnered from the
	 *     [Auth] instance.  If this doesn't work (or, obviously, if there is no
	 *     Auth) throw an exception.
	 *  2. When a `$dbname` is provided, repeat the above, but ignore any
	 *     database name given in the config file.
	 *
	 * Also, if a database name is provided, then a group of the same name is
	 * used if found in the Database config file.
	 *
	 * @return boolean True if connected (throws Exception otherwise)
	 * @throws Exception if unable to connect.
	 */
	public function connect()
	{
		if ( ! isset($this->_connection))
		{
			try {
				$hostname = $this->_config['connection']['hostname'];
				$this->_connection = mysql_connect($hostname, $this->username(), $this->password());
			} catch (Exception $e) {
				$message = 'Unable to connect to the DBMS as '.$this->username();
				throw new Database_Exception($message);
				
			}
		}
		return true;
	}

	/**
	 * Get a list of all databases visible to the current database user.  The
	 * 'information_schema' is omitted.  DB names are cached.
	 * 
	 * @param boolean $refresh_cache Don't use the cache, but rebuild it.
	 * @return array[string] List of all available databases.
	 */
	public function list_dbs($refresh_cache = FALSE)
	{
		$token = Profiler::start('WebDB', __METHOD__);

		if ( ! $this->_connection) return array();
		
		// Check cache
		$cache = Cache::instance();
		$cache_key = 'database_names_'.Auth::instance()->get_user();
		$this->_database_names = $cache->get($cache_key);
		
		// If not cached, query DB
		if ( ! is_array($this->_database_names) OR $refresh_cache)
		{
			$this->_database_names = array();
			$query = mysql_query("SHOW DATABASES", $this->_connection);
			while ($row = mysql_fetch_row($query))
			{
				$db_name = $row[0];
				// Exclude information_schema
				if (strtoupper($db_name) != 'INFORMATION_SCHEMA')
				{
					$this->_database_names[] = $db_name;
				}
			}
			// Plugins
			Plugins::call('classes.webdb.dbms.list-dbs', $this->_database_names);

			// Cache
			Kohana::$log->add(Kohana_Log::DEBUG, "Caching DB names under $cache_key.");
			$cache->set($cache_key, $this->_database_names);
		}

		Profiler::stop($token);
		return $this->_database_names;
	}

	/**
	 * Refresh all cached DB metadata.  Called when a user logs in, or
	 * permissions are changed, or any other times when the visible data is
	 * liable to have been changed.
	 * 
	 * @return void
	 */
	public function refresh_cache()
	{
		if ( ! $this->_connection)
		{
			$this->connect();
		}
		$this->list_dbs(TRUE);
	}

	/**
	 * Get password from config or Auth.
	 *
	 * @return void
	 */
	public function password()
	{
		$token = Profiler::start('WebDB', __METHOD__);
		if (empty($this->_config['connection']['password']))
		{
			$auth = Auth::instance();
			$password = $auth->password($auth->get_user());
			$this->_config['connection']['password'] = $password;
		}
		return $this->_config['connection']['password'];
		Profiler::stop($token);
	}

	/**
	 * Get username from config or Auth.  If it's retrieved from Auth, the DB
	 * config is updated with this value.
	 * 
	 * @return string The username
	 */
	public function username()
	{
		if (empty($this->_config['connection']['username']))
		{
			$this->_config['connection']['username'] = Auth::instance()->get_user();
		}
		return $this->_config['connection']['username'];
	}

	/**
	 * Get a database object.  If no name is given, try to determine the current
	 * database from the 'dbname' route variable.  If that fails, return false.
	 *
	 * @param string $dbname The name of the desired database.
	 * @return WebDB_DBMS_Database The database object.
	 * @return false If no database could be found.
	 * @throws Exception
	 */
	public function get_database($dbname = FALSE)
	{
		if ( ! $dbname)
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
		if ( ! in_array($dbname, $this->list_dbs()))
		{
			throw new Exception("The database '$dbname' could not be found.");
		}
		$this->_config['connection']['database'] = $dbname;
		$this->_db = Database::instance(NULL, $this->_config);
		$this->_db->connect();
		return new WebDB_DBMS_Database($this, $dbname);
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
		$config = Kohana::$config->load('webdb');

		// See if WebDB permissions are being used.
		if ( ! isset($config->permissions) OR empty($config->permissions['table']))
		{
			return $default_permissions;
		}

		// Fully-qualify the database name.
		$db_name = '';
		if ( ! empty($config->permissions['database'])) 
		{
			$db_name = $config->permissions['database'].'.';
		}

		// For individual permissions tables per database, see if the
		// permissions table exists in the current database.
		if (empty($db_name) AND ! empty($config->permissions['table'])) {
			if ($current_db = $this->get_database()) {
				if ( ! in_array($config->permissions['table'], $current_db->list_tables())) {
					return $default_permissions;
				}
			}
		}

		// Finally, fetch the permissions rows.
		$query = new Database_Query_Builder_Select;
		$query->from($db_name.$config->permissions['table']);
		$query->where('identifier', 'IN', array(Auth::instance()->get_user(), '*'));
		$rows = $query->execute($this->_db)->as_array();
		return $rows;
	}

}
