<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A class for parsing CSV files.
 *
 * @package  WebDB
 * @category Base
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_File_CSV
{

	public $data;

	public $name;

	public function  __construct($filename)
	{
		$this->name = basename($filename);
		$this->data = array();
        $file = fopen($filename, 'r');
        while (($line = fgetcsv($file)))
		{
			$this->data[] = $line;
        }
        fclose($file);

//        $this->view->columns = $this->view->data[0];
//        $this->view->columnSelects = array();
//        unset($this->data[0]);
//        for ($colNum=0; $colNum<count($this->view->columns); $colNum++) {
//            $this->view->columnSelects[$this->view->columns[$colNum]]
//                = $this->getFieldSelect($colNum, $this->view->columns[$colNum]);
//        }
	}

	public function reassign_columns($array)
	{
		$out = array();
		foreach ($array as $key=>$val)
		{
			if (isset($data[0][$val]))
			{

			}
		}
		return $out;
	}

//	public function get_headers()
//	{
//		return $this->data[0];
//		$out = array('');
//		foreach ($this->data[0] as $header)
//		{
//			$out[] = $header;
//		}
//	}

}