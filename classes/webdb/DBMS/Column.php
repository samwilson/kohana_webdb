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
	 * @var PearScaff_DB_Table The table to which this column belongs.
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
	private $_required = false;

	/** @var boolean Whether or not this column is the Primary Key. */
	private $_isPK = false;

	/** @var mixed The default value for this column. */
	private $_default;

	/** @var boolean Whether or not this column is auto-incrementing. */
	private $_isAutoIncrement = false;

	/**
	 * @var string A comma-separated list of the privileges that the database
	 * user has for this column.
	 * For example: 'select,insert,update,references'
	 */
	private $_db_user_privileges;

	/** @var string The comment attached to this column. */
	private $_comment;

	/**
	 * @var PearScaff_DB_Table|false The table that this column refers to, or
	 * false if it is not a foreign key.
	 */
	private $_references = false;

	/**
	 *
	 * @param <type> $info
	 */
	public function __construct(Webdb_DBMS_Table $table, $info)
	{
		$info = array_combine(array_map('strtolower', array_keys($info)), array_values($info));
		//echo '<pre>'.kohana::dump($info).'</pre>';

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
		// Comment
		$this->_comment = $info['comment'];
		// NULL?
		if ($info['null'] == 'NO')
		{
			$this->required = TRUE;
		}
		// Is this a foreign key?
		if (in_array($this->_name, $table->get_foreign_key_names()))
		{
			$referencedTables = $table->getReferencedTables();
			$this->_references = $referencedTables[$this->_name];
		}
		// DB user privileges
		$this->_db_user_privileges = $info['privileges'];

		// App user privileges
		//$this->_appUserPrivileges = $this->_getAppUserPrivileges();

	}

	public function is_editable()
	{
		return true;
	}


	/**
	 * Find out whether the database user (as opposed to the application user)
	 * has the given privilege or privileges on this column.
	 *
	 * @param $privilege string The comma-delimited list of privileges to check.
	 * @return boolean
	 */
	public function db_user_can($privilege)
	{
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
	 * Get this column's type.
	 *
	 * @return string The type of this column.
	 */
	public function get_type()
	{
		return $this->_type;
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
	public function isRequired()
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
	 * @return PearScaff_DB_Table The referenced table.
	 */
	public function get_referenced_table()
	{
		return $this->_table->getDatabase()->getTable($this->_references);
	}

	/**
	 * @return string|false The name of the referenced table or false if this is
	 * not a foreign key.
	 */
	public function getReferencedTableName()
	{
		return $this->_references;
	}

	/**
	 * Get the table that this column belongs to.
	 *
	 * @return PearScaff_DB_Table The table object.
	 */
	public function getTable()
	{
		return $this->_table;
	}

	/**
	 *
	 * @param <type> $type_string
	 */
	private function parse_type($type_string)
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
		$integer_pattern = '/.*?(int)\(+(\d+)\)/';

		if (preg_match($varchar_pattern, $type_string, $matches))
		{
			$this->_type = $matches[1];
			$this->_size = $matches[2];
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
			$this->_size = $matches[2];
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
		return "$this->_name $this->_type$size$pk$auto$ref\n";
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

