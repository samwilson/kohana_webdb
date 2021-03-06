<?php
/**
 *
 * @package  WebDB
 * @category DBMS
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class WebDB_DBMS_Table {

	/** @var Database The Database instance in use. */
	private $_db;
	/** @var WebDB_DBMS_Database The database to which this table belongs. */
	private $_database;
	/** @var string The name of this table. */
	private $_name;
	/** @var string The SQL statement used to create this table. */
	private $_defining_sql;
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

	/** @var array */
	private $_filters = array();

	/** @var array Permitted operators. */
	private $_operators = array(
		'like'        => 'contains',
		'not like'    => 'does not contain',
		'='           => 'is',
		'!='          => 'is not',
		'empty'       => 'is empty',
		'not empty'   => 'is not empty',
		'>='          => 'is greater than or equal to',
		'>'           => 'is greater than',
		'<='          => 'is less than or equal to',
		'<'           => 'is less than'
	);
	
	/**
	 * @var integer|false The number of currently-filtered rows, or false if no
	 * query has been made yet or the filters have been reset.
	 */
	private $_row_count = FALSE;

	/** @var array Each joined table gets a unique alias, based on this. */
	protected $alias_count = 1;

	/**
	 * Create a new database table object.  Queries the database or cache for
	 * information about the table's columns, and creates new Webdb_DBMS_Column
	 * objects for each.
	 *
	 * @param WebDB_DBMS_Database $db The database to which this table belongs.
	 * @param string $name The name of the table.
	 */
	public function __construct($db, $name)
	{
		$this->_database = $db;
		$this->_db = $db->get_db();
		$this->_name = $name;
		if ( ! isset($this->_columns))
		{
			$this->_columns = array();
			
			// Check cache for columns info
			$cache = Cache::instance();
			$cache_key = 'columns_info'.$this->_database->get_name().$this->get_name().$this->_database->get_dbms()->username();
			$columns_info = $cache->get($cache_key);

			// If not cached, fetch and build an array of the raw info, then
			// cache that
			if ( ! is_array($columns_info))
			{
				$columns_info = array();
				$sql = 'SHOW FULL COLUMNS FROM '.$this->_db->quote_table($name);
				$column_query = $this->_db->query(Database::SELECT, $sql);
				foreach ($column_query as $column_info)
				{
					$columns_info[] = $column_info;
				}
				$cache->set($cache_key, $columns_info);
			}
			
			// Create column objects
			foreach ($columns_info as $column_info)
			{
				$column = new WebDB_DBMS_Column($this, $column_info);
				$this->_columns[$column->get_name()] = $column;
			}
		}
	}

	public function add_filter($column, $operator, $value, $raw = FALSE)
	{
		$valid_columm = in_array($column, array_keys($this->_columns));
		$valid_operator = in_array($operator, array_keys($this->_operators));
		$value_required = strpos($operator, 'empty') === FALSE;
		$valid_value = (( ! $value_required) OR ($value_required AND ! empty($value)));
		if ($valid_columm AND $valid_operator AND $valid_value)
		{
			$this->_filters[] = array(
				'column'    => $column,
				'operator'  => $operator,
				'value'     => trim($value),
				'raw'       => $raw,
			);
		}
	}

	/**
	 * Add all of the filters given in $_GET['filters'].  This is used in both
	 * the [index](api/Controller_WebDB#action_index)
	 * and [export](api/Controller_WebDB#action_export) actions.
	 *
	 * @return void
	 */
	public function add_get_filters()
	{
		$filters = Arr::get($_GET, 'filters', array());
		if (is_array($filters))
		{
			foreach ($filters as $filter) {
				$column = arr::get($filter, 'column', FALSE);
				$operator = arr::get($filter, 'operator', FALSE);
				$value = arr::get($filter, 'value', FALSE);
				$this->add_filter($column, $operator, $value);
			}
		}
	}

	/**
	 *
	 * @param Database_Query_Builder_Select $query
	 */
	public function apply_filters( & $query)
	{
		$profiler = Profiler::start('WebDB', get_class().'::'.__METHOD__);
		foreach ($this->_filters as $filter)
		{
			// 'Raw' filters don't require translation
			if ($filter['raw'] === TRUE)
			{
				$col_name = $this->get_name().'.'.$filter['column'];
				$query->where($col_name, $filter['operator'], $filter['value']);
				continue;
			}

			$column = $this->_columns[$filter['column']];
			$filter['column'] = $this->join_for($column, $query);

			// LIKE or NOT LIKE
			if ($filter['operator']=='like' OR $filter['operator']=='not like')
			{
				$filter['value'] = '%'.$filter['value'].'%';
				$filter['column'] = DB::expr('CONVERT('.$filter['column'].', CHAR)');
			}

			// IS EMPTY
			if ($filter['operator'] == 'empty')
			{
				$query->where($filter['column'], 'IS', NULL);
				$query->or_where($filter['column'], '=', '');
				$filter['column'] = '';
			}

			// IS NOT EMPTY
			if ($filter['operator'] == 'not empty')
			{
				$query->where($filter['column'], 'IS NOT', NULL);
				$query->and_where($filter['column'], '!=', '');
				$filter['column'] = '';
			}

			if ( ! empty($filter['column']))
			{
				$query->where($filter['column'], $filter['operator'], $filter['value']);
			}

		} // end foreach filter

		Profiler::stop($profiler);
	}

	/**
	 * For a given foreign key column, join the given query to the foreign table
	 * and return the alias for use in selecting against the foreign table.
	 * 
	 * If the column is not a foreign key, the alias will just be the qualified
	 * column name, and no join will be done.
	 * 
	 * @param WebDB_DBMS_Column $column The column (usually a FK).
	 * @param Database_Query $query The query.
	 * 
	 * @return array Array with 'join_clause' and 'column_alias' keys
	 */
	public function join_for($column, & $query)
	{
		$token = Profiler::start('WebDB', __METHOD__);

		if ( ! $column->is_foreign_key())
		{
			return $this->get_name().'.'.$column->get_name();
		}
		$fk1_table = $column->get_referenced_table();
		$fk1_title_column = $fk1_table->get_title_column();
		$fk1_alias = 'f'.$this->alias_count;
		$query->join(array($fk1_table->get_name(), $fk1_alias), 'LEFT OUTER')
			  ->on($this->_name.'.'.$column->get_name(), '=', $fk1_alias.'.id');
		$alias = $fk1_alias.'.'.$fk1_title_column->get_name();
		// FK is also an FK?
		if ($fk1_title_column->is_foreign_key())
		{
			$fk2_table = $fk1_title_column->get_referenced_table();
			$fk2_title_column = $fk2_table->get_title_column();
			$fk2_alias = 'ff'.$this->alias_count;
			$query->join(array($fk2_table->get_name(), $fk2_alias), 'LEFT OUTER')
				  ->on($fk1_alias.'.'.$fk1_title_column->get_name(), '=', $fk2_alias.'.id');
			$alias = $fk2_alias.'.'.$fk2_title_column->get_name();
		}
		$this->alias_count++;

		Profiler::stop($token);
		return $alias;
	}

	/**
	 *
	 * @param Database_Query_Builder_Select $query
	 */
	public function apply_ordering( & $query)
	{
		$this->orderby = Arr::get($_GET, 'orderby', '');
		$this->orderdir = (Arr::get($_GET, 'orderdir')=='desc') ? 'desc' : 'asc';
		if ( ! in_array($this->orderby, array_keys($this->get_columns())))
		{
			$this->orderby = $this->get_title_column()->get_name();
		}
		if ($this->get_column($this->orderby)->is_foreign_key())
		{
			$fk1_alias = 'o1';
			$fk1_table = $this->get_column($this->orderby)->get_referenced_table();
			$query->join(array($fk1_table->get_name(), $fk1_alias), 'LEFT OUTER');
			$query->on($this->get_name().'.'.$this->orderby, '=', "$fk1_alias.id");
			$orderby = $fk1_alias.'.'.$fk1_table->get_title_column()->get_name();
			if ($fk1_table->get_title_column()->is_foreign_key())
			{
				$fk2_alias = 'o2';
				$fk2_table = $fk1_table->get_title_column()->get_referenced_table();
				$query->join(array($fk2_table->get_name(), $fk2_alias), 'LEFT OUTER');
				$query->on($fk1_alias.'.'.$fk1_table->get_title_column()->get_name(), '=', "$fk2_alias.id");
				$orderby = $fk2_alias.'.'.$fk2_table->get_title_column()->get_name();
			}
			$query->order_by($orderby, $this->orderdir);
		} else
		{
			$query->order_by($this->get_name().'.'.$this->orderby, $this->orderdir);
		}
	}

	/**
	 * Get rows, with pagination.
	 *
	 * Note that rows are returned as arrays and not objects, because MySQL
	 * allows column names to begin with a number, but PHP does not.
	 *
	 * @return Database_Result The row data
	 */
	public function get_rows($with_pagination = TRUE, $consider_total = TRUE)
	{
		$token = Profiler::start('WebDB', __METHOD__);

		// First get all columns and rows (leaving column selection for now).
		$query = new Database_Query_Builder_Select;
		$query->from($this->get_name());
		$this->apply_filters($query);
		$this->apply_ordering($query);

		// Then limit to the ones on the current page.
		if ($with_pagination)
		{
			// If paginated, load the 'title'.
			$title_alias = $this->join_for($this->get_title_column(), $query);
			$query->select(array($title_alias, 'webdb_title'));
			$pagination = $this->get_pagination($consider_total);
			$query->offset($pagination->offset);
			$query->limit($pagination->items_per_page);
		}

		// Select columns and do query.
		$this->select_columns($query);

		$this->_rows = $query->execute($this->_db);
		Profiler::stop($token);
		return $this->_rows;
	}

	/**
	 * Add this table's columns to a given select query, optionally with joining
	 * to any foreign tables and adding FK title column aliases.
	 * @uses Database_Query_Builder_Select::select()
	 * @param Database_Query_Builder_Select $query
	 * @param boolean $with_fk_titles Whether to add FK title column aliases
	 * @return void
	 */
	private function select_columns( & $query, $with_fk_titles = TRUE)
	{
		$token = Profiler::start('WebDB', __METHOD__);
		foreach ($this->get_columns() as $col)
		{
			$query->select($this->get_name().'.'.$col->get_name());
			// For FKs, also select a special 'title' column.
			if ($with_fk_titles AND $col->is_foreign_key())
			{
				$alias = $this->join_for($col, $query);
				$query->select(array($alias, $col->get_name().'_webdb_title'));
			}
		}
		Profiler::stop($token);
	}

	/**
	 * Get a single row as an associative array.
	 * 
	 * Foreign keys garner an additional column, suffixed with '_webdb_title',
	 * that is the title of the remote row.
	 * 
	 * @param integer $id The ID of the row to get.
	 * @param boolean $with_fk_titles Whether to include the 'title' columns.
	 * @return array
	 */
	public function get_row($id, $with_fk_titles = TRUE)
	{
		$token = Profiler::start('WebDB', __METHOD__);
		$query = new Database_Query_Builder_Select;
		$this->select_columns($query, $with_fk_titles);
		$query->from($this->get_name());
		$query->limit(1);
		$pk_column = $this->get_pk_column();
		$pk_name = ( ! $pk_column) ? 'id' : $pk_column->get_name();
		$query->where($this->get_name().'.'.$pk_name, '=', $id);
		$row = $query->execute($this->_db)->current();
		Profiler::stop($token);
		return $row;
	}

	public function get_default_row()
	{
		$row = array();
		foreach ($this->get_columns() as $col)
		{
			$row[$col->get_name()] = $col->get_default();
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
	 * Get a list of permitted operators.
	 *
	 * @return array[string]=>string List of operators.
	 */
	public function get_operators()
	{
		return $this->_operators;
	}

	/**
	 * Get the pagination object for this table. Optionally ignore the total
	 * number of records, for when this is irrelevant (e.g. for autocomplete
	 * pagination).
	 *
	 * @param boolean $with_total Whether to consider the total number of rows.
	 * @return Pagination
	 */
	public function get_pagination($with_total = TRUE)
	{
		if ( ! isset($this->_pagination))
		{
			$total_row_count = $with_total ? $this->count_records() : 0;
			$config = array('total_items' => $total_row_count);
			$this->_pagination = new Pagination($config);
		}
		return $this->_pagination;
	}

	/**
	 * Get the number of rows in the current filtered set.  This leaves the
	 * actual counting up to `$this->get_rows()`, rather than doing the query
	 * itself, because filtering is applied in that method, and I didn't want to
	 * duplicate that here (or anywhere else).
	 *
	 * @todo Rename this to `row_count()`.
	 * @return integer
	 */
	public function count_records()
	{
		if ( ! $this->_row_count)
		{
			$query = new Database_Query_Builder_Select;
			$query->select(array(DB::expr('COUNT(*)'), 'total'));
			$query->from($this->get_name());
			$this->apply_filters($query);
			$result = $query->execute($this->_db);
			$total = $result->current();
			$this->_row_count = $total['total'];
		}
		return $this->_row_count;
	}

	public function export()
	{
		$export_dir = Kohana::$cache_dir.DIRECTORY_SEPARATOR.'exports';
		@mkdir($export_dir);
		$filename = $export_dir.DIRECTORY_SEPARATOR.uniqid().'.csv';
		if (Kohana::$is_windows)
		{
			$filename = str_replace('\\', '/', $filename);
		}

		$headers_query = new Database_Query_Builder_Select;
		$data_query = new Database_Query_Builder_Select;
		$headers_query->union($data_query);
		$alias_num = 1;
		foreach ($this->get_columns() as $col)
		{
			$header = "'".WebDB_Text::titlecase($col->get_name())."'";
			$headers_query->select(DB::expr($header));

			if ($col->is_foreign_key())
			{
				$fk1_alias = "e$alias_num";
				$alias_num++;
				$fk1_table = $col->get_referenced_table();
				$data_query->join(array($fk1_table->get_name(), $fk1_alias), 'LEFT OUTER');
				$data_query->on($this->get_name().'.'.$col->get_name(), '=', "$fk1_alias.id");
				$select = $fk1_alias.'.'.$fk1_table->get_title_column()->get_name();
				if ($fk1_table->get_title_column()->is_foreign_key())
				{
					$fk2_alias = "e$alias_num";
					$alias_num++;
					$fk2_table = $fk1_table->get_title_column()->get_referenced_table();
					$data_query->join(array($fk2_table->get_name(), $fk2_alias), 'LEFT OUTER');
					$data_query->on($fk1_alias.'.'.$fk1_table->get_title_column()->get_name(), '=', "$fk2_alias.id");
					$select = $fk2_alias.'.'.$fk2_table->get_title_column()->get_name();
				}
			} else
			{
				$select = $this->get_name().'.'.$col->get_name();
			}
			$data_query->select(DB::expr("REPLACE(IFNULL($select, ''),'\r\n', '\n')"));

		}
		$data_query->from($this->get_name());
		$this->add_get_filters();
		$this->apply_filters($data_query);
		$data_query->outfile($filename);
		$headers_query->outfile();
		$headers_query->execute($this->_db);

		if ( ! file_exists($filename))
		{
			throw new Kohana_Exception("Export file not created: $filename");
		}
		return $filename;
	}

	/**
	 * Get one of this table's columns.
	 *
	 * @return Webdb_DBMS_Column The column.
	 */
	public function get_column($name)
	{
		return $this->_columns[$name];
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
	 * Get the table comment text.
	 *
	 * @return string
	 */
	public function get_comment()
	{
		$sql = $this->_get_defining_sql();
		$comment_pattern = '/.*\)(?:.*COMMENT.*\'(.*)\')?/si';
		preg_match($comment_pattern, $sql, $matches);
		return (isset($matches[1])) ? $matches[1] : '';
	}

	/**
	 * Get the title text for a given row.  This is the value of the 'title
	 * column' for that row.  If the title column is a foreign key, then the
	 * title of the foreign row is used (this is recursive, to allow FKs to
	 * reference FKs to an arbitrary depth).
	 *
	 * @param integer $id The row ID.
	 * @return string The title of this row.
	 */
	public function get_title($id)
	{
		$row = $this->get_row($id);
		$title_column = $this->get_title_column();
		// If the title column is  FK, pass the title request through.
		if ($title_column->is_foreign_key())
		{
			$fk_row_id = $row[$title_column->get_name()];
			return $title_column->get_referenced_table()->get_title($fk_row_id);
		}
		// Otherwise, get the text.
		return Arr::get($row, $title_column->get_name(), implode(' | ', $row));
	}

	/**
	 * Get the first unique-keyed column, or if there is no unique non-ID column
	 * then use the second column (because this is often a good thing to do).
	 * Unless there's only one column; then, just use that.
	 * 
	 * @return Webdb_DBMS_Column
	 */
	public function get_title_column()
	{
		// Try to get the first unique key
		foreach ($this->get_columns() as $column)
		{
			if ($column->is_unique_key()) return $column;
		}
		// But if that fails, just use the second (or the first) column.
		$column_indices = array_keys($this->_columns);
		$title_col_name = Arr::get($column_indices, 1, Arr::get($column_indices, 0, 'id'));
		return $this->_columns[$title_col_name];
	}

	/**
	 * Get the SQL statement used to create this table, as given by the 'SHOW
	 * CREATE TABLE' command.
	 *
	 * @return string The SQL statement used to create this table.
	 */
	private function _get_defining_sql()
	{
		// If already loaded
		if ( ! empty($this->_defining_sql))
		{
			return $this->_defining_sql;
		}
		
		// Check cache
		$cache = Cache::instance();
		$cache_key = 'defining_sql'.$this->_database->get_name().$this->get_name().$this->get_database()->get_dbms()->username();
		
		$this->_defining_sql = $cache->get($cache_key);
		
		// Retrieve from DB
		if (empty($this->_defining_sql))
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
				throw new Kohana_Exception('Table not found: '.$this->_name);
			}
			$cache->set($cache_key, $defining_sql);
			$this->_defining_sql = $defining_sql;
		}
		return $this->_defining_sql;
	}

	/**
	 *
	 */
	public function get_permissions()
	{
		$out = array();
		foreach ($this->_database->get_permissions() as $perm) {
			if ($perm['table_name']=='*' OR $perm['table_name']==$this->_name) {
				$out[] = $perm;
			}
		}
		return $out;
	}
	
	/**
	 * Get this table's Primary Key column.
	 * 
	 * @return Webdb_DBMS_Column The PK column.
	 */
	public function get_pk_column()
	{
		foreach ($this->get_columns() as $column)
		{
			if ($column->is_primary_key()) return $column;
		}
		return FALSE;
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
		if ( ! isset($this->_referenced_tables))
		{
			$defining_sql = $this->_get_defining_sql();
			$fk_pattern = '|FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)`|';
			preg_match_all($fk_pattern, $defining_sql, $matches);
			if (isset($matches[1]) AND count($matches[1])>0)
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
	 * @return array Of the format: `array('table' => Webdb_DBMS_Table, 'column' => string)`
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
					$out[] = array('table'  => $table, 'column' => $foreign_column);
				}
			}
		}
		return $out;
	}

	public function get_filters()
	{
		return $this->_filters;
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
	 * Find out whether or not the current user has the given permission for any
	 * of the records in this table.
	 *
	 * @return boolean
	 */
	public function can($perm)
	{
		foreach ($this->get_columns() as $column)
		{
			if ($column->can($perm))
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Get the database to which this table belongs.
	 *
	 * @return WebDB_DBMS_Database The database object.
	 */
	public function get_database()
	{
		return $this->_database;
	}

	public function get_one_line_summary()
	{
		$col_count = count($this->get_columns());
		return $this->_name." ($col_count columns)";
	}

	/**
	 * Get a string representation of this table; a succinct summary of its
	 * columns and their types, keys, etc.
	 *
	 * @return string A summary of this table.
	 */
	public function __toString()
	{
		$col_count = count($this->get_columns());
		$out = "\n+-----------------------------------------+\n";
		$out .= "| ".$this->_name." ($col_count columns)\n";
		$out .= "+-----------------------------------------+\n";
		foreach ($this->get_columns() as $column)
		{
			$out .= "| $column \n";
		}
		$out .= "+-----------------------------------------+\n\n";
		return $out;
	}

	/**
	 * Get an XML representation of the structure of this table.
	 *
	 * @return DOMElement The XML 'table' node.
	 */
	public function to_xml()
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$table = $dom->createElement('table');
		$dom->appendChild($table);
		$name = $dom->createElement('name');
		$name->appendChild($dom->createTextNode($this->_name));
		$table->appendChild($name);
		foreach ($this->get_columns() as $column)
		{
			$table->appendChild($dom->importNode($column->to_xml(), true));
		}
		return $table;
	}

	/**
	 * Get JSON-encoded metadata of this table.
	 *
	 * @return string JSON-encoded medata
	 */
	public function to_json()
	{
		$json = new Services_JSON;
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
	 * Remove all filters.
	 *
	 * @return void
	 */
	public function reset_filters()
	{
		$this->_filters = array();
		$this->_row_count = FALSE;
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
		// Get columns and Primary Key name etc.
		$columns = $this->get_columns();
		$pk_name = $this->get_pk_column()->get_name();
		$has_pk = ! empty($data[$pk_name]);

		/*
		 * Check permissions on each column.
		*/
		foreach ($columns as $column_name=>$column)
		{
			// Ignore this column if we're not trying to save it.
			if ( ! isset($data[$column_name]))
			{
				continue;
			}
			
			$can_update = $column->can('update');
			$can_insert = $column->can('insert');
			if ($column_name != $pk_name
				AND (( ! $can_update AND $has_pk) OR ( ! $can_insert AND ! $has_pk)))
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
			if ( ! isset($columns[$field]))
			{
				unset($data[$field]);
				continue;
			}

			$column = $columns[$field];

			/*
			 * Booleans
			*/
			if ($column->is_boolean())
			{
				if (($value == NULL OR $value == '') AND ! $column->is_required())
				{
					$data[$field] = NULL;
				} elseif ($value === '0'
					OR $value === 0
					OR strcasecmp($value,'false') === 0
					OR strcasecmp($value,'off') === 0
					OR strcasecmp($value,'no') === 0)
				{
					$data[$field] = 0;
				} else
				{
					$data[$field] = 1;
				}
			}

			/*
			 * Nullable empty fields should be NULL.
			 */
			elseif ( ! $column->is_required() AND empty($value))
			{
				$data[$field] = NULL;
			}

			/*
			 * Foreign keys
			*/
			elseif ($column->is_foreign_key() AND ($value <= 0 OR $value == ''))
			{
				$data[$field] = NULL;
			}

			/*
			 * Numbers
			*/
			elseif ( ! is_numeric($value)
				AND (substr($column->get_type(),0,3)=='int'
				OR substr($column->get_type(),0,7)=='decimal'
				OR substr($column->get_type(),0,5)=='float'))
			{
				$data[$field] = NULL; // Stops empty strings being turned into 0s.
			}

			/*
			 * Dates & times
			*/
			elseif (($column->get_type()=='date' OR $column->get_type()=='datetime' OR $column->get_type()=='time')
				AND $value=='')
			{
				$data[$field] = null;
			}
		}

		// Update?
		if ($has_pk AND $this->get_row($data[$pk_name], FALSE))
		{
			$pk_val = $data[$pk_name];
			unset($data[$pk_name]);
			// If there's nothing left to update, give up.
			if (empty($data)) return false;
			DB::update($this->get_name())
				->set($data)
				->where($pk_name, '=', $pk_val)
				->execute($this->_db);
		}
		// Or insert?
		else
		{
			$query = DB::insert($this->get_name())
				->columns(array_keys($data))
				->values($data)
				->execute($this->_db);
			$pk_val = $query[0]; // Database::query() returns array (insert id, row count) for INSERT queries.
			// Insert ID does not apply to non-auto-increment PKs, so we can
			// use whatever was POSTed (and thus just saved):
			if ($pk_val==0) 
			{
				$pk_val = $data[$pk_name];
			}
		}
		return $pk_val;
	}

}