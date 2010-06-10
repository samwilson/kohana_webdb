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

	/** @var Database The Database instance in use. */
	private $_db;
	/** @var Webdb_DBMS_Database The database to which this table belongs. */
	private $_database;
	/** @var string The name of this table. */
	private $_name;
	/** @var string The SQL statement used to create this table. */
	private $_definingSql;
	/**
	 * @var array[string => Webdb_DBMS_Table] Array of tables referred to by
	 * columns in this one.
	 */
	private $_referenced_tables;
	/**
	 * @var array[string => Webdb_DBMS_Column] Array of column names and
	 * objects for all of the columns in this table.
	 */
	private $_columns;

	/** @var Pagination */
	private $_pagination;

	/**
	 * Create a new database table object.
	 *
	 * @param Webdb_DBMS_Database The database to which this table belongs.
	 * @param string $name The name of the table.
	 */
	public function __construct($db, $name)
	{
		$this->_database = $db;
		$this->_db = $db->get_db();
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
	 * Get rows, with pagination.
	 *
	 * @return <type>
	 */
	public function get_rows()
	{
		//return array();
		//$query = $this->_db->query(Database::SELECT, '', TRUE);
		$query = new Database_Query_Builder_Select();
		$query->as_object();
		$query->from($this->get_name());
		if (isset($this->where[0]) && isset($this->where[1]) && isset($this->where[2]))
		{
			$query->where($this->where[0], $this->where[1], $this->where[2]);
		}
		$query->offset($this->get_pagination()->offset);
		$query->limit($this->get_pagination()->items_per_page);
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
		$row = $query->execute($this->_db)->current();
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
	 * @return Webdb_DBMS_Database The database to which this table belongs.
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
	 * Get the title text for a given row.
	 *
	 * @param integer $id
	 */
	public function get_title($id)
	{
		$row = $this->get_row($id);
		$title_column = $this->get_title_column()->get_name();
		return $row->$title_column;
	}

	/**
	 * @return Webdb_DBMS_Column
	 */
	public function get_title_column()
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
	public function get_referenced_tables()
	{
		if (!isset($this->_referenced_tables))
		{
			$definingSql = $this->_get_defining_sql();
			$foreignKeyPattern = '|FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)`|';
			preg_match_all($foreignKeyPattern, $definingSql, $matches);
			if (isset($matches[1]) && count($matches[1])>0)
			{
				$this->_referenced_tables = array_combine($matches[1], $matches[2]);
			} else
			{
				$this->_referenced_tables = array();
			}
		}
		return $this->_referenced_tables;
	}

	/**
	 * Get tables with foreign keys referring here.
	 *
	 * @return array
	 */
	public function get_referencing_tables()
	{
		$out = array();
		foreach ($this->_database->get_tables() as $table)
		{
			$foreign_tables = $table->get_referenced_tables();
			foreach ($foreign_tables as $foreign_column => $foreign_table)
			{
				if ($foreign_table == $this->_name)
				{
					$out[$foreign_column] = $table;
				}
			}
		}
		return $out;
	}

	/**
	 * Get a list of the names of the foreign keys in this table.
	 *
	 * @return array[string] Names of foreign key columns in this table.
	 */
	public function get_foreign_key_names()
	{
		return array_keys($this->get_referenced_tables());
	}

	/**
	 * Find out whether or not the current user can update any of the records in
	 * this table.
	 *
	 * @return boolean
	 */
	public function can_update()
	{
		foreach ($this->get_columns() as $column)
		{
			if ($column->can_update())
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Find out whether or not the current user can insert new records into this
	 * table.
	 *
	 * @return boolean
	 */
	public function can_insert()
	{
		foreach ($this->get_columns() as $column)
		{
			if ($column->can_insert())
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Find out whether or not the current user can select any of the records in
	 * this table.
	 *
	 * @return boolean
	 */
	public function can_select()
	{
		foreach ($this->get_columns() as $column)
		{
			if ($column->can_select())
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Get the database to which this table belongs.
	 *
	 * @return Webdb_DBMS_Database The database object.
	 */
	public function get_database()
	{
		return $this->_database;
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
	public function save_row($data)
	{
		//exit(kohana::debug($data));

		$columns = $this->get_columns();

		/*
		 * Check permissions on each column.
		*/
		foreach ($columns as $column_name=>$column)
		{
			if (!isset($data[$column_name]))
			{
				continue;
			}
			$can_update = $column->can_update();
			$can_insert = $column->can_insert();
			if ($column_name != 'id' && (
				(!$can_update && isset($data['id'])) || (!$can_insert && !isset($data['id']))
			))
			{
				unset($data[$column_name]);
			}
		}

		/*
		 * Go through all data and clean it up before saving.
		*/
		foreach ($data as $field=>$value)
		{

			// Make sure this column exists in the DB.
			if (!isset($columns[$field]))
			{
				unset($data[$field]);
				continue;
			}

			$column = $columns[$field];

			/*
			 * Booleans
			*/
			if ($column->get_type() == 'tinyint')
			{
				if ($value == NULL || $value == '')
				{
					$data[$field] = NULL;
				} elseif ($value === '0'
					|| $value === 0
					|| strcasecmp($value,'false') === 0
					|| strcasecmp($value,'off') === 0
					|| strcasecmp($value,'no') === 0)
				{
					$data[$field] = 0;
				} else
				{
					$data[$field] = 1;
				}
			}

			/*
			 * Foreign keys
			*/
			elseif ( $column->is_foreign_key() && ($value <= 0 || $value == '') )
			{
				$data[$field] = NULL;
			}

			/*
			 * Numbers
			*/
			elseif (!is_numeric($value)
				&& (substr($column->get_type(),0,3)=='int'
					||substr($column->get_type(),0,7)=='decimal'
					||substr($column->get_type(),0,5)=='float')
			)
			{
				$data[$field] = NULL; // Stops empty strings being turned into 0s.
			}

			/*
			 * Dates & times
			*/
			elseif ( ($column->get_type()=='date' || $column->get_type()=='datetime' || $column->get_type()=='time') && $value=='')
			{
				$data[$field] = null;
			}
		}

		//exit(kohana::debug($data));

		// Update?
		if (isset($data['id']) && is_numeric($data['id']))
		{
			$id = $data['id'];
			unset($data['id']);
			DB::update($this->get_name())
				->set($data)
				->where('id', '=', $id)
				->execute($this->_db);
			//$this->dbAdapter->update($table, $data, "id = $id");
		}
		// Or insert?
		else
		{
			$id = DB::insert($this->get_name())
				->columns(array_keys($data))
				->values($data)
				->execute($this->_db);
			$id = $id[0]; // Database::query() returns array (insert id, row count) for INSERT queries.
		}
		return $id;
	}

}