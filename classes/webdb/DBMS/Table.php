<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 * @package  WebDB
 * @category DBMS
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_DBMS_Table
{

	/** @var Database The database to which this table belongs. */
	private $_db;
	/** @var string The name of this table. */
	private $_name;
	/** @var string The SQL statement used to create this table. */
	private $_definingSql;
	/**
	 * @var array[string => PearScaff_DB_Table] Array of tables referred to by
	 * columns in this one.
	 */
	private $_referencedTables;
	/**
	 * @var array[string => PearScaff_DB_Column] Array of column names and
	 * objects for all of the columns in this table.
	 */
	private $_columns;

	/** @var Pagination */
	private $_pagination;

	/**
	 * Create a new database table object.
	 *
	 * @param string $name The name of the table.
	 */
	public function __construct($db, $name)
	{
		$this->_db = $db;
		$this->_name = $name;
		if (!isset($this->_columns))
		{
			$this->_columns = array();
			$columns_info = $this->_db->query(
				Database::SELECT,
				'SHOW FULL COLUMNS FROM '.$this->_db->quote_table($name),
				FALSE
			);
			//$columns_info = $this->_db->list_columns($this->_name);
			//var_dump($columnsInfo);
			foreach ($columns_info as $column_info)
			{
				$column = new Webdb_DBMS_Column($this, $column_info);
				$this->_columns[$column->get_name()] = $column;
			}
		}
	}

	/**
	 *
	 * @param <type> $id
	 * @return <type>
	 */
	public function get_rows($id = FALSE)
	{
		//return array();
		//$query = $this->_db->query(Database::SELECT, '', TRUE);
		$query = new Database_Query_Builder_Select();
		$query->as_object();
		$query->from($this->get_name());
		$query->offset($this->get_pagination()->offset);
		$query->limit($this->get_pagination()->items_per_page);
		if ($id)
		{
			$query->where('id', '=', $id);
		}
		$rows = $query->execute($this->_db);
		//exit('<pre>'.kohana::dump($rows->as_array()));
		return $rows;
	}

	/**
	 *
	 * @param <type> $id
	 */
	public function get_row($id)
	{
		$query = new Database_Query_Builder_Select();
		$query->as_object();
		$query->from($this->get_name());
		$query->limit(1);
		$query->where('id', '=', $id);
		$row = $query->execute($this->_db);
		return $row;
	}

	public function get_default_row()
	{
		$row = new stdClass();
		foreach ($this->get_columns() as $col)
		{
			$row->{$col->get_name()} = $col->get_default();
		}
		return $row;
	}

	/**
	 * Get this table's database object.
	 *
	 * @return PearScaff_DB_Database The database to which this table belongs.
	 */
	/*public function getDatabase()
	{
		return $this->_db;
	}*/

	/**
	 * Get this table's name.
	 *
	 * @return string The name of this table.
	 */
	public function get_name()
	{
		return $this->_name;
	}

	/**
	 * Get a pagination object for this table.
	 *
	 * @return Pagination
	 */
	public function get_pagination()
	{
		if (!isset($this->_pagination))
		{
			$total_row_count = $this->count_records();
			//$view = View::factory('pagination/basic');
			$config = array('total_items' => $total_row_count); //, 'view'=>$view);
			$this->_pagination = new Pagination($config);
		}
		return $this->_pagination;
	}

	/**
	 *
	 * @return integer
	 */
	public function count_records()
	{
		return $this->_db->count_records($this->_name);
	}

	/**
	 * Get a list of this table's columns.
	 *
	 * @return array[Webdb_DBMS_Column] This table's columns.
	 */
	public function get_columns()
	{
		return $this->_columns;
	}

	/**
	 *
	 */
	public function getTitleColumn()
	{
		$columnIndices = array_keys($this->_columns);
		$titleColName = $columnIndices[1];
		return $this->_columns[$titleColName];
	}

	/**
	 * Get the SQL statement used to create this table, as given by the 'SHOW
	 * CREATE TABLE' command.
	 *
	 * @return string The SQL statement used to create this table.
	 */
	private function _get_defining_sql()
	{
		if (!isset($this->_definingSql))
		{
			$defining_sql = $this->_db->query(Database::SELECT, "SHOW CREATE TABLE `$this->_name`", TRUE);
			if ($defining_sql->count() > 0)
			{
				$defining_sql->next();
				$defining_sql = $defining_sql->as_array();
				$defining_sql = $defining_sql[0];
				if (isset($defining_sql->{'Create Table'}))
				{
					$defining_sql = $defining_sql->{'Create Table'};
				}
				elseif (isset($defining_sql->{'Create View'}))
				{
					$defining_sql = $defining_sql->{'Create View'};
				}
			} else
			{
				throw new Exception('Table not found: '.$this->_name);
			}
		}
		$this->_definingSql = $defining_sql;
		return $defining_sql;
	}

	/**
	 * Get a list of a table's foreign keys and the tables to which they refer.
	 * This does <em>not</em> take into account a user's permissions (i.e. the
	 * name of a table which the user is not allowed to read may be returned).
	 *
	 * @return array[string => string] The list of <code>column_name => table_name</code> pairs.
	 */
	public function getReferencedTables()
	{
		if (!isset($this->_referencedTables))
		{
			$definingSql = $this->_get_defining_sql();
			$foreignKeyPattern = '|FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)`|';
			preg_match_all($foreignKeyPattern, $definingSql, $matches);
			if (isset($matches[1]) && count($matches[1])>0)
			{
				$this->_referencedTables = array_combine($matches[1], $matches[2]);
			} else
			{
				$this->_referencedTables = array();
			}
		}
		return $this->_referencedTables;
	}

	/**
	 * Get a list of the names of the foreign keys in this table.
	 *
	 * @return array[string] Names of foreign key columns in this table.
	 */
	public function get_foreign_key_names()
	{
		return array_keys($this->getReferencedTables());
	}

	/**
	 * Find out whether or not the current user can edit any of the records in
	 * this table.
	 *
	 * First check that the MySQL user itself has permission to
	 * edit the table; if it doesn't, then any application-level privileges will
	 * fail also.  To count as having edit privileges, the MySQL user must have
	 * insert or update privileges on at least one column in the table.
	 *
	 * Next, see if the database has the required user access
	 * tables.  If it does, query those to see whether the user can edit or not.
	 *
	 * @return boolean
	 */
	public function can_edit()
	{

		// Check that the database user can edit.
		$db_user_can_edit = false;
		foreach ($this->get_columns() as $column)
		{
			if ($column->db_user_can('update,insert'))
			{
				$db_user_can_edit = true;
				// As soon as we know the DB user can edit at least one column,
				// we can say that they can edit the table.
				break;
			}
		}

		// Check that the application user can edit this table.

		$appUserCanEdit = true; //$this->_db->getUser()->canEdit($this->_name);

		// Return the conjunction of these.
		return $db_user_can_edit && $appUserCanEdit;

	}

	/**
	 * Find out whether or not the current user can view any of the records in
	 * this table.
	 *
	 * First check that the MySQL user itself has SELECT permission on the
	 * table; if it doesn't, then any application-level privileges will fail
	 * also.
	 *
	 * Next, see if the database has the required user access tables.  If it
	 * does, query those to see whether the user can edit or not.
	 *
	 * @return boolean
	 */
	public function canView()
	{

		// Check that the database user can edit.
		$dbUserCanView = false;
		foreach ($this->get_columns() as $column)
		{
			if ($column->dbUserCan('select'))
			{
				$dbUserCanView = true;
				break;
			}
		}

		// Check that the application user can view this table.
		$appUserCanView = $this->_db->getUser()->canView($this->_name);

		return $dbUserCanView && $appUserCanView;
	}

	public function getOneLineSummary()
	{
		$colCount = count($this->get_columns());
		return $this->_name . " ($colCount columns)";
	}

	/**
	 * Get a string representation of this table; a succinct summary of its
	 * columns and their types, keys, etc.
	 *
	 * @return string A summary of this table.
	 */
	public function __toString()
	{
		$colCount = count($this->get_columns());
		$out = "\n+-----------------------------------------+\n";
		$out .= "| " . $this->_name . " ($colCount columns)\n";
		$out .= "+-----------------------------------------+\n";
		foreach ($this->get_columns() as $column)
		{
			$out .= "| " . $column . "\n";
		}
		$out .= "+-----------------------------------------+\n\n";
		return $out;
	}

	/**
	 * Get an XML representation of the structure of this table.
	 *
	 * @return DOMElement The XML 'table' node.
	 */
	public function toXml()
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$table = $dom->createElement('table');
		$dom->appendChild($table);
		$name = $dom->createElement('name');
		$name->appendChild($dom->createTextNode($this->_name));
		$table->appendChild($name);
		foreach ($this->get_columns() as $column)
		{
			$table->appendChild($dom->importNode($column->toXml(), true));
		}
		return $table;
	}

	/**
	 *
	 * @return <type>
	 */
	public function toJson()
	{
		$json = new Services_JSON();
		$metadata = array();
		foreach ($this->get_columns() as $column)
		{
			$metadata[] = array(
				'name' => $column->getName()
			);
		}
		return $json->encode($metadata);
	}

	/**
	 * Save data to this table.  If the 'id' key of the data array is numeric,
	 * the row with that ID will be updated; otherwise, a new row will be
	 * inserted.
	 *
	 * @param array  $data  The data to insert; if 'id' is set, update.
	 * @return int          The ID of the updated or inserted row.
	 */
	public function save($data)
	{
		$blobs = array();

		$columnsInfo = $this->get_columns($table);

		// Check permissions
		foreach ($data as $field=>$value)
		{
			if (!isset($columnsInfo[$field]))
			{
				continue;
			}
			$canUpdate = strpos($columnsInfo[$field]['privileges'],'update');
			$canInsert = strpos($columnsInfo[$field]['privileges'],'insert');
			if ($field != 'id'
				&& (
				(!$canUpdate && isset($data['id']))
					|| (!$canInsert && !isset($data['id']))
			))
			{
				unset($data[$field]);
			}
		}

		// Go through all data and clean it up before saving.
		foreach ($data as $name=>$value)
		{

			// Make sure this column exists in the DB.
			if (!isset($columnsInfo[$name]))
			{
				unset($data[$name]);
				continue;
			}

			$colInfo = $columnsInfo[$name];

			// Booleans
			if ($colInfo['type']=='tinyint')
			{
				if ($value==null || $value=='')
				{
					$data[$name] = null;
				} elseif ($value==='0'
					|| $value===0
					|| strcasecmp($value,'false')===0
					|| strcasecmp($value,'off')===0
					|| strcasecmp($value,'no')===0)
				{
					$data[$name] = 0;
				} else
				{
					$data[$name] = 1;
				}
			}

			// Foreign keys
			elseif ($colInfo['references'] && ($value<=0||$value==''))
			{
				$data[$name] = null;
			}

			// Numbers
			elseif (!is_numeric($value)
				&& (substr($colInfo['type'],0,3)=='int'
					||substr($colInfo['type'],0,7)=='decimal'
					||substr($colInfo['type'],0,5)=='float')
			)
			{
				$data[$name] = null; // Stops empty strings being turned into 0s.
			}

			// Dates & times
			elseif ( ($colInfo['type']=='date' || $colInfo['type']=='datetime' || $colInfo['type']=='time') && $value=='')
			{
				$data[$name] = null;
			}
		}

		// Update?
		if (isset($data['id']) && is_numeric($data['id']))
		{
			$id = $data['id'];
			unset($data['id']);
			$this->dbAdapter->update($table, $data, "id = $id");
		}
		// Or insert?
		else
		{
			$this->dbAdapter->insert($table, $data);
			$id = $this->dbAdapter->lastInsertId();
		}
		return $id;
	}

}