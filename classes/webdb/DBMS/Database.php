<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Notes:
 *
 * Individual Webdb_DBMS_Database objects are not constructed when a
 * [Webdb_DBMS] is, but only when needed.
 *
 * @package  WebDB
 * @category DBMS
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_DBMS_Database
{

	/** @var Database The [Kohana_Database] object. */
	private $_db;

	private $_name;

	/**
	 * Create a new Webdb_DBMS_Database object, getting a [Database] instance
	 * (i.e. creating on if neccessary) using the application config file and
	 * the provided database name.
	 *
	 * @todo If the config file doesn't contain a username & password, prompt for same.
	 * @param string $dbname The database's name.
	 * @return void
	 */
	public function __construct($dbname)
	{
		$this->_name = $dbname;
		$config = Kohana::config('database')->default;
		$config['connection']['database'] = $dbname;
		//echo '<pre>'.kohana::dump($config).'</pre>';
		unset(Database::$instances[$dbname]);
		$this->_db = Database::instance($dbname, $config);
	}

	/**
	 *
	 * @return string The name of the current database;
	 */
	public function get_name()
	{
		return $this->_name;
	}

	/**
	 * See [Database][list_tables].
	 */
	public function list_tables($like = NULL)
	{
		return $this->_db->list_tables();
	}

	/**
	 *
	 * @param <type> $tablename
	 * @return Webdb_DBMS_Database
	 */
	public function get_table($tablename = FALSE)
	{
		if (!$tablename)
		{
			$tablename = Request::instance()->param('tablename', FALSE);
			if ($tablename)
			{
				return $this->get_table($tablename);
			} else
			{
				return false;
			}
		}
		if (!in_array($tablename, $this->list_tables()))
		{
			throw new Exception("The table '$tablename' could not be found.");
		}
		return new Webdb_DBMS_Table($this->_db, $tablename);
	}

}

