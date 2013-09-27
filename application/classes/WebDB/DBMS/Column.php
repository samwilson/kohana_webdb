<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 * @package  WebDB
 * @category DBMS
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_DBMS_Column
{

	/**
	 * @var Webdb_DBMS_Table The table to which this column belongs.
	 */
	private $_table;

	/** @var string The name of this column. */
	private $_name;

	/** @var string The type of this column. */
	private $_type;

	/** @var integer The size, or length, of this column. */
	private $_size;

	/** @var string This column's collation. */
	private $_collation;

	/**
	 * @var boolean Whether or not this column is required, i.e. is NULL = not
	 * required = false; and NOT NULL = required = true.
	 */
	private $_required = FALSE;

	/** @var boolean Whether or not this column is the Primary Key. */
	private $_isPK = FALSE;
	
	/** @var boolean Whether or not this column is a Unique Key. */
	private $_isUnique = FALSE;

	/** @var mixed The default value for this column. */
	private $_default;

	/** @var boolean Whether or not this column is auto-incrementing. */
	private $_isAutoIncrement = FALSE;

	/**
	 * @var string A comma-separated list of the privileges that the database
	 * user has for this column.
	 * For example: 'select,insert,update,references'
	 */
	private $_db_user_privileges;

	/** @var string The comment attached to this column. */
	private $_comment;

	/**
	 * @var Webdb_DBMS_Table|false The table that this column refers to, or
	 * false if it is not a foreign key.
	 */
	private $_references = FALSE;

	/**
	 *
	 * @param <type> $info
	 */
	public function __construct(Webdb_DBMS_Table $table, $info)
	{
		$info = array_combine(array_map('strtolower', array_keys($info)), array_values($info));

		// Table object
		$this->_table = $table;

		// Name
		$this->_name = $info['field'];

		// Type
		$this->parse_type($info['type']);

		// Default
		$this->_default = $info['default'];

		// Primary key
		if (strtoupper($info['key']) == 'PRI')
		{
			$this->_isPK = true;
			if ($info['extra'] == 'auto_increment')
			{
				$this->_isAutoIncrement = true;
			}
		}

		// Unique key
		if (strtoupper($info['key']) == 'UNI')
		{
			$this->_isUnique = true;
		}
		
		// Comment
		$this->_comment = $info['comment'];

		// Collation
		$this->_collation = $info['collation'];

		// NULL?
		if ($info['null'] == 'NO')
		{
			$this->_required = TRUE;
		}

		// Is this a foreign key?
		if (in_array($this->_name, $table->get_foreign_key_names()))
		{
			$referencedTables = $table->get_referenced_tables();
			$this->_references = $referencedTables[$this->_name];
		}

		// DB user privileges
		$this->_db_user_privileges = $info['privileges'];

	}

	public function can($perm)
	{
		return $this->_db_user_can($perm) && $this->_app_user_can($perm);
	}

	/**
	 * Check that the current user can edit this column.  This uses the
	 * permissions table to lookup which roles have edit permission, and then
	 * asks [Auth] whether the current user has any of these roles.
	 *
	 * If the config permissions table is empty, assume all permissions.
	 *
	 * @return boolean
	 */
	private function _app_user_can($priv_type)
	{
		foreach ($this->_table->get_permissions() as $perm) {
			$columns = explode(',', $perm['column_names']);
			$can_column = $perm['column_names']=='*' || in_array($this->_name, $columns);
			$can_permission = $perm['permission']=='*' || stripos($perm['permission'], $priv_type)!==FALSE;
			$can_identifier = $perm['identifier']=='*'
				|| $perm['identifier']==Auth::instance()->get_user()
				|| Auth::instance()->logged_in($perm['identifier']);
			if ($can_column && $can_permission && $can_identifier) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Find out whether the database user (as opposed to the application user)
	 * has any of the given privileges on this column.
	 *
	 * @param $privilege string The comma-delimited list of privileges to check.
	 * @return boolean
	 */
	public function _db_user_can($privilege)
	{
		$db_privs = array('select','update','insert','delete');
		if (!in_array($privilege, $db_privs)) {
			return FALSE;
		}
		$has_priv = false;
		$privs = explode(',', $privilege);
		foreach ($privs as $priv)
		{
			if (strpos($this->_db_user_privileges, $priv) !== false)
			{
				$has_priv = true;
			}
		}
		return $has_priv;
	}

	/**
	 * Get this column's name.
	 *
	 * @return string The name of this column.
	 */
	public function get_name()
	{
		return $this->_name;
	}

	/**
	 * Get the valid options for this column; only applies to ENUM and SET.
	 *
	 * @return array The available options.
	 */
	public function get_options()
	{
		return $this->_options;
	}

	/**
	 * Get this column's type.
	 *
	 * @return string The type of this column.
	 */
	public function get_type()
	{
		return $this->_type;
	}

	/**
	 * Get the column's comment.
	 *
	 * @return string
	 */
	public function get_comment()
	{
		return $this->_comment;
	}

	/**
	 * Get the default value for this column.
	 *
	 * @return mixed
	 */
	public function get_default()
	{
		return $this->_default;
	}

	/**
	 * Does this column have a default value?
	 *
	 * @return boolean
	 */
	public function has_default()
	{
		return $this->get_default() != NULL;
	}

	/**
	 * Get this column's size.
	 *
	 * @return integer The size of this column.
	 */
	public function get_size()
	{
		return $this->_size;
	}

	/**
	 * Whether or not a non-NULL value is required for this column.
	 *
	 * @return boolean True if this column is NOT NULL, false otherwise.
	 */
	public function is_required()
	{
		return $this->_required;
	}

	/**
	 * Whether or not this column is the Primary Key for its table.
	 *
	 * @return boolean True if this is the PK, false otherwise.
	 */
	public function isPrimaryKey()
	{
		return $this->_isPK;
	}

	/**
	 * Whether or not this column is a unique key.
	 *
	 * @return boolean True if this is a Unique Key, false otherwise.
	 */
	public function is_unique_key()
	{
		return $this->_isUnique;
	}

	/**
	 * Whether or not this column is a tinyint of length 1 (i.e MySQL's idea of
	 * a boolean).
	 * 
	 * @return boolean
	 */
	public function is_boolean()
	{
		return ($this->get_type()=='tinyint') AND ($this->get_size()==1);
	}

	/**
	 * Whether or not this column is a foreign key.
	 *
	 * @return boolean True if $this->_references is not empty, otherwise false.
	 */
	public function is_foreign_key()
	{
		return !empty($this->_references);
	}

	/**
	 * Get the table object of the referenced table, if this column is a foreign
	 * key.
	 *
	 * @return WebDB_DBMS_Table The referenced table.
	 */
	public function get_referenced_table()
	{
		//exit(kohana::debug($this->_table));
		return $this->_table->get_database()->get_table($this->_references);
	}

	/**
	 * @return string|false The name of the referenced table or false if this is
	 * not a foreign key.
	 */
	/*public function get_referenced_table_name()
	{
		return $this->_references;
	}*/

	/**
	 * Get the table that this column belongs to.
	 *
	 * @return Webdb_DBMS_Table The table object.
	 */
	public function get_table()
	{
		return $this->_table;
	}

	/**
	 *
	 * @param string $type_string
	 */
	public function parse_type($type_string)
	{
		//echo '<pre>Start: '.kohana::dump($type_string).'</pre>';
		//exit();
		if (preg_match('/unsigned/', $type_string))
		{
			$this->_unsigned = true;
		}

		$varchar_pattern = '/^((?:var)?char)\((\d+)\)/';
		$decimal_pattern = '/^decimal\((\d+),(\d+)\)/';
		$float_pattern   = '/^float\((\d+),(\d+)\)/';
		$integer_pattern = '/^((?:big|medium|small|tiny)?int)\(?(\d+)\)?/';
		$enum_pattern = '/^(enum|set)\(\'(.*?)\'\)/';

		if (preg_match($varchar_pattern, $type_string, $matches))
		{
			$this->_type = $matches[1];
			$this->_size = (int) $matches[2];
		} elseif (preg_match($decimal_pattern, $type_string, $matches))
		{
			$this->_type = 'decimal';
			//$colData['precision'] = $matches[1];
			//$colData['scale'] = $matches[2];
		} elseif (preg_match($float_pattern, $type_string, $matches))
		{
			$this->_type = 'float';
			//$colData['precision'] = $matches[1];
			//$colData['scale'] = $matches[2];
		} elseif (preg_match($integer_pattern, $type_string, $matches))
		{
			$this->_type = $matches[1];
			$this->_size = (int) $matches[2];
		} elseif (preg_match($enum_pattern, $type_string, $matches))
		{
			$this->_type = $matches[1];
			$values = explode("','",$matches[2]);
			$this->_options = array_combine($values, $values);
		} else
		{
			$this->_type = $type_string;
		}
	}

	public function __toString()
	{
		$pk = ($this->_isPK) ? ' PK' : '';
		$auto = ($this->_isAutoIncrement) ? ' AI' : '';
		if ($this->_references)
		{
			$ref = ' References '.$this->_references . '.';
		} else
		{
			$ref = '';
		}
		$size = ($this->_size > 0) ? "($this->_size)" : '';
		return "$this->_name $this->_type$size$pk$auto$ref";
	}

	/**
	 * Get an XML representation of the structure of this column.
	 *
	 * @return DOMElement The XML 'column' node.
	 */
	public function toXml()
	{
		// Set up
		$dom = new DOMDocument('1.0', 'UTF-8');
		$table = $dom->createElement('column');
		$dom->appendChild($table);

		// name
		$name = $dom->createElement('name');
		$name->appendChild($dom->createTextNode($this->get_name()));
		$table->appendChild($name);

		// references
		$references = $dom->createElement('references');
		$references->appendChild($dom->createTextNode($this->getReferencedTableName()));
		$table->appendChild($references);

		// size
		$size = $dom->createElement('size');
		$size->appendChild($dom->createTextNode($this->get_size()));
		$table->appendChild($size);

		// type
		$type = $dom->createElement('type');
		$type->appendChild($dom->createTextNode($this->get_type()));
		$table->appendChild($type);

		// primarykey
		$primarykey = $dom->createElement('primarykey');
		$primarykey->appendChild($dom->createTextNode($this->isPrimaryKey()));
		$table->appendChild($primarykey);

		// type
		$required = $dom->createElement('required');
		$required->appendChild($dom->createTextNode($this->isRequired()));
		$table->appendChild($required);

		// Finish
		return $table;
	}

}

