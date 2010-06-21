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

	/** @var string The name of the database. */
	private $_name;

	/** @var array[Webdb_DBMS_Table] Array of [Webdb_DBMS_Table] objects. */
	private $_tables;

	/**
	 * Create a new Webdb_DBMS_Database object
	 *
	 * @param Database The database driver.
	 * @param string $dbname The database's name.
	 * @return void
	 */
	public function __construct($dbms, $dbname)
	{
		$this->_name = $dbname;
		$this->_tables = array();
		$this->dbms = $dbms;
		$this->_db = $this->dbms->get_database_driver();
	}

	/**
	 * Get this database's database (if you see what I mean?).
	 *
	 * @return Database The database instance currently in use.
	 */
	public function get_db()
	{
		return $this->_db;
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
	 * Get all table names, optionally filtered by a given string.
	 *
	 * @param string $like The filter string.
	 * @return array[string] Array of table names.
	 * @uses Database::list_tables
	 */
	public function list_tables($like = NULL)
	{
		return $this->_db->list_tables($like);
	}

	/**
	 * Get a list of tables of this database.
	 *
	 * @return array[Webdb_DBMS_Table] Array of [Webdb_DBMS_Table] objects.
	 */
	public function get_tables($like = NULL)
	{
		$tablenames = $this->_db->list_tables($like);
		foreach ($tablenames as $tablename)
		{
			$this->get_table($tablename);
		}
		return $this->_tables;
	}

	public function get_permissions()
	{
		$out = array();
		foreach ($this->dbms->get_permissions() as $perm) {
			if ($perm['database_name']=='*' OR $perm['database_name']==$this->_name) {
				$out[] = $perm;
			}
		}
		return $out;
	}

	/**
	 * Get a table object.
	 *
	 * @param string $tablename
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
		if (!in_array($tablename, $this->_db->list_tables()))
		{
			throw new Exception("The table '$tablename' could not be found.");
		}
		if (!isset($this->_tables[$tablename]))
		{
			$table = new Webdb_DBMS_Table($this, $tablename);
			if ($table->can('select'))
			{
				$this->_tables[$tablename] = $table;
			} else
			{
				return FALSE;
			}
		}
		return $this->_tables[$tablename];
	}

}

