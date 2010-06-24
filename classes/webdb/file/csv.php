<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A class for parsing a CSV file has either just been uploaded (i.e. $_FILES is set),
 * or is stored as a temporary file (as defined herein).
 *
 * @package  WebDB
 * @category Base
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_File_CSV
{

	public $headers;

	public $data;

	/** @var string Temporary identifier for CSV file. */
	public $hash = FALSE;

	/**
	 * Create a new CSV object based on a file.
	 *
	 * 1. If a file is being uploaded (i.e. `$_FILES['file']` is set), attempt to use it as the CSV file.
	 * 2. On the otherhand, if there is a Request `id` parameter, attempt to use this to locate a local temporary file.
	 *
	 * In either case, if a valid CSV file cannot be found and parsed, throw an exception.
	 *
	 * @return Webdb_File_CSV
	 */
	public function  __construct()
	{
		if (arr::get($_FILES, 'file', FALSE))
		{
			$this->_get_from_FILES();
		}
		
		$hash = Request::instance()->param('id', FALSE);
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
		$validation = Validate::factory($_FILES, 'uploads');
		$validation
			->rule('file', 'upload::not_empty')
			->rule('file', 'upload::type', array(array('csv')));
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
					case 'upload::not_empty':
						throw new Kohana_Exception('You did not choose a file to upload!');
					case 'upload::type':
						throw new Kohana_Exception('You can only import CSV files.');
					default:
						throw new Kohana_Exception('An error occured.<br />'.kohana::debug($err));
				}
			}
		}

	}

	private function _load_data()
	{
		$file_path = sys_get_temp_dir().$this->hash;
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
	 * Whether or not a file has been successfully loaded.
	 *
	 * @return boolean
	 */
	public function loaded()
	{
		return $this->hash !== FALSE;
	}

	/**
	 * Rename all keys in all data rows to match DB column names.
	 *
	 * If a _value_ in the array matches a lowercased DB column header, the _key_
	 * of that value is the DB column name to which that header has been matched.
	 *
	 * @param array $array
	 * @return void
	 */
	public function match_fields($array)
	{
		// First get the indexes of the headers
		foreach ($array as $key=>$val)
		{
			foreach ($this->headers as $head_num=>$head_name)
			{
				if (strtolower($head_name) == $val)
				{
					$heads[$head_num] = $key;
				}
			}
		}
		//echo Kohana::debug($array);
		//echo Kohana::debug($this->headers);
		//echo Kohana::debug($heads);
		// Now rename the keys in all the data rows
		foreach ($this->data as &$row)
		{
			$new_row = array();
			foreach ($row as $cell_num => $value)
			{
				//unset($row[$cell_num]);
				if (isset($heads[$cell_num]))
				{
					$new_row[$heads[$cell_num]] = $value;
				}
			}
			//echo Kohana::debug($new_row);
			$row = $new_row;
		}
		//echo Kohana::debug($this->data);
		//exit();
	}

}