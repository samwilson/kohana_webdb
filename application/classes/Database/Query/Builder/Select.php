<?php defined('SYSPATH') or die('No direct script access.');

class Database_Query_Builder_Select extends Kohana_Database_Query_Builder_Select
{
	
	protected $_outfile;

	public function outfile($filename = NULL)
	{
		$this->_type = Database::UPDATE;
		if ( ! empty($filename))
		{
			$this->_outfile = (string) $filename;
		}
		return $this;
	}

	public function compile($db = NULL)
	{
		parent::compile($db);
		if ( ! empty($this->_outfile))
		{
			$outfile = ' INTO OUTFILE \''.$this->_outfile.'\''
				.' FIELDS TERMINATED BY ","'
				.' OPTIONALLY ENCLOSED BY \'"\''
				.' ESCAPED BY \'"\''
				.' LINES TERMINATED BY "\r\n"';
			$this->_sql .= $outfile;
			($this->_sql);
		}
		return $this->_sql;
	}
}