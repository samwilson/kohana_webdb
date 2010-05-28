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

	/** @var boolean */
	public $logged_in = FALSE;

	/**
	 */
	public function __construct()
	{
		$this-> _db = Database::instance();
		try
		{
			$this-> _db->connect();
			
		} catch (Exception $e)
		{
			/*
			 * First connection failure: try to use credentials from the session.
			*/
			$config = kohana::config('database')->default;
			$username = session::instance()->get('username', FALSE);
			if ($username)
			{
				$config['connection']['username'] = $username;
			}
			$password = session::instance()->get('password', FALSE);
			if ($password)
			{
				$config['connection']['password'] = $password;
			}

			unset(Database::$instances['default']);
			$this-> _db = Database::instance('default', $config);

			try
			{
				$this-> _db->connect();
				$this->logged_in = TRUE;

			} catch (Exception $e)
			{
				/*
				* Second connection failure: give up.
				*/
				throw new Webdb_DBMS_ConnectionException('Unable to connect to DBMS.');
			}
		}

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
			$query = Database::instance()->query(Database::SELECT, $sql, true);
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

		return new Webdb_DBMS_Database($dbname);
	}

}
