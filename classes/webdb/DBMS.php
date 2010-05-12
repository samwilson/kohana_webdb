<?php
/**
 * This class represents an instance of the DBMS.  With it, we connect
 * and query, and retrieve @link{Webdb_DBMS_Database} objects.
 *
 * @category WebDB
 * @package  WebDB
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

	/**
	 */
	public function __construct()
	{
		$this->_db = Database::instance();
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

	public function list_tables($dbname)
	{
		return $this->_db->list_tables();
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
	 *
	 * @return Database
	 */
	public function get_current_database()
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

	/**
	 * Get a database.
	 *
	 * @param string $dbname The name of the desired database.
	 * @return Database The database object.
	 * @throws Exception
	 */
	public function get_database($dbname = null)
	{
		// Get first DB
		if (empty($dbname))
		{
			throw new Exception('Database name is empty.');
		}
		if (!in_array($dbname, $this->list_dbs()))
		{
			throw new Exception("The database '$dbname' could not be found.");
		}
		//return new Webdb_DBMS_Database($this->_db, $name);

		$config = Kohana::config('database')->default;
		$config['connection']['database'] = $dbname;
		//echo '<pre>'.kohana::dump($config).'</pre>';
		unset(Database::$instances['default']);
		$this->_db = Database::instance('default', $config);
		return $this->_db;
	}

}
