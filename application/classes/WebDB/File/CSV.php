<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A class for parsing a CSV file has either just been uploaded (i.e. $_FILES is set),
 * or is stored as a temporary file (as defined herein).
 *
 * @package  WebDB
 * @category Helpers
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_File_CSV
{

	public $headers;

	/** @var array two-dimenstional integer-indexed array of the CSVs data */
	public $data;

	/** @var string Temporary identifier for CSV file. */
	public $hash = FALSE;

	/**
	 * Create a new CSV object based on a file.
	 *
	 * 1. If a file is being uploaded (i.e. `$_FILES['file']` is set), attempt to use it as the CSV file.
	 * 2. On the otherhand, if we're given a hash, attempt to use this to locate a local temporary file.
	 *
	 * In either case, if a valid CSV file cannot be found and parsed, throw an exception.
	 *
	 * @return Webdb_File_CSV
	 */
	public function  __construct($hash = FALSE)
	{
		if (Arr::get($_FILES, 'file', FALSE))
		{
			$this->_get_from_FILES();
		}
		
		if ($hash)
		{
			$this->hash = $hash;
		}
		
		if ($this->hash)
		{
			$this->_load_data();
		}
	}

	private function _get_from_FILES()
	{
		$validation = Validation::factory($_FILES, 'uploads');
		$validation
			->rule('file', 'Upload::not_empty')
			->rule('file', 'Upload::type', array(':value', array('csv')));
		if ($validation->check())
		{
			$this->hash = md5(time());
			Upload::save($_FILES['file'], $this->hash, sys_get_temp_dir());
		} else
		{
			foreach ($validation->errors() as $err)
			{
				switch($err[0])
				{
					case 'Upload::not_empty':
						throw new Kohana_Exception('You did not choose a file to upload!');
					case 'Upload::type':
						throw new Kohana_Exception('You can only import CSV files.');
					default:
						throw new Kohana_Exception('An error occured.<br />'.Debug::vars($err));
				}
			}
		}

	}

	private function _load_data()
	{
		$file_path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->hash;
		if (!file_exists($file_path))
		{
			throw new Kohana_Exception("No import was found with the identifier &lsquo;$this->hash&rsquo;");
		}

		// Get all rows.
		$this->data = array();
		$file = fopen($file_path, 'r');
		while (($line = fgetcsv($file)))
		{
			$this->data[] = $line;
		}
		fclose($file);

		// Extract headers.
		$this->headers = $this->data[0];
		unset($this->data[0]);
	}

	/**
	 * Get the number of data rows in the file (i.e. excluding the header row).
	 * 
	 * @return integer The number of rows.
	 */
	public function row_count()
	{
		return count($this->data);
	}

	/**
	 * Whether or not a file has been successfully loaded.
	 *
	 * @return boolean
	 */
	public function loaded()
	{
		return $this->hash !== FALSE;
	}

	private function remap($column_map)
	{
		$heads = array();
		foreach ($column_map as $db_col_name => $csv_col_name)
		{
			foreach ($this->headers as $head_num => $head_name)
			{
				if (strtolower($head_name) == $csv_col_name)
				{
					$heads[$head_num] = $db_col_name;
				}
			}
		}
		return $heads;
	}

	/**
	 * Rename all keys in all data rows to match DB column names, and normalize
	 * all values to be valid for the `$table`.
	 *
	 * If a _value_ in the array matches a lowercased DB column header, the _key_
	 * of that value is the DB column name to which that header has been matched.
	 *
	 * @param Webdb_DBMS_Table $table
	 * @param array $columns
	 * @return array Array of error messages.
	 */
	public function match_fields($table, $column_map)
	{
		// First get the indexes of the headers
		$heads = $this->remap($column_map);

		$errors = array();
		for ($row_num = 1; $row_num <= $this->row_count(); $row_num++)
		{
			foreach ($this->data[$row_num] as $col_num => $value)
			{
				if ( ! isset($heads[$col_num]))
				{
					continue;
				}
				$col_errors = array();
				$db_column_name = $heads[$col_num];
				$column = $table->get_column($db_column_name);
				// Required, but empty
				if ($column->is_required() AND empty($value)) {
					$col_errors[] = 'Required but empty';
				}
				// Already exists
				if ($column->is_unique_key())
				{
					// @TODO
				}
				// Too long (if the column has a size and the value is greater than this)
				if ( ! $column->is_foreign_key() AND ! $column->is_boolean()
					AND $column->get_size() > 0
					AND strlen($value) > $column->get_size()
					)
				{
					$col_errors[] = 'Value ('.$value.') too long (maximum length of '.$column->get_size().')';
				}
				// Invalid foreign key value
				if (!empty($value) && $column->is_foreign_key())
				{
					$err = $this->validate_foreign_key($column, $col_num, $row_num, $value);
					if ($err) $col_errors[] = $err;
				}

				if (count($col_errors) > 0)
				{
					// Construct error details array
					$errors[] = array(
						'column_name' => $this->headers[$col_num],
						'column_number' => $col_num,
						'field_name' => $column->get_name(),
						'row_number' => $row_num,
						'messages' => $col_errors,
					);
				}
			}
		}
		return $errors;
	}

	/**
	 * Assume all data is now valid, and only FK values remain to be translated.
	 * 
	 * @param WebDB_Table
	 * @param array $column_map array of DB names to import names.
	 * @return integer The number of rows imported.
	 */
	public function import_data($table, $column_map)
	{
		$count = 0;
		$headers = $this->remap($column_map);
		for ($row_num = 1; $row_num <= $this->row_count(); $row_num++)
		{
			$row = array();
			foreach ($this->data[$row_num] as $col_num => $value)
			{
				if ( ! isset($headers[$col_num]))
				{
					continue;
				}
				$db_column_name = $headers[$col_num];
				$column = $table->get_column($db_column_name);
				if ($column->is_foreign_key())
				{
					$foreign_row = $this->get_fk_rows($column->get_referenced_table(), $value)->current();
					$pk = $column->get_referenced_table()->get_pk_column()->get_name();
					$value = $foreign_row[$pk];
				}
				
				$row[$db_column_name] = $value;
			}
			$table->save_row($row);
			$count++;
		}
		return $count;
	}

	/**
	 * Determine whether a given value is valid for a foreign key (i.e. is the
	 * title of a foreign row).
	 * 
	 * @param Webdb_DBMS_Column $column
	 * @param integer $col_num
	 * @param integer $row_num
	 * @param string $value
	 * @return FALSE if the value is valid
	 * @return array error array if the value is not valid
	 */
	public function validate_foreign_key($column, $col_num, $row_num, $value)
	{
		$foreign_table = $column->get_referenced_table();
		if ( ! $this->get_fk_rows($foreign_table, $value)->valid())
		{
			$route_params = array(
				'action' => 'index',
				'dbname' => $foreign_table->get_database()->get_name(),
				'tablename' => $foreign_table->get_name()
			);
			$uri = Route::url('default', $route_params, TRUE);
			$a_params = array(
				'target' => '_blank',
				'title' => 'Opens in a new tab or window',
			);
			$link = HTML::anchor($uri, Webdb_Text::titlecase($foreign_table->get_name()), $a_params);
			return 'Value ('.$value.') not found in '.$link;
		}
		return FALSE;
	}

	/**
	 * Get the rows of a foreign table where the title column equals a given
	 * value.
	 * 
	 * @param WebDB_DBMS_Table $foreign_table
	 * @param string $value The value to match against the title column.
	 * @return Database_Result
	 */
	private function get_fk_rows($foreign_table, $value)
	{
		$foreign_table->reset_filters();
		$foreign_table->add_filter($foreign_table->get_title_column()->get_name(), '=', $value);
		return $foreign_table->get_rows();
	}
}