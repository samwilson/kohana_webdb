<?php defined('SYSPATH') or die('No direct script access.');
/**
 * This exception is thrown when we are not able to connect to the database.
 *
 * @package  WebDB
 * @category DBMS
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Webdb_DBMS_ConnectionException extends Kohana_Exception
{

	public function __construct($message)
	{
		parent::__construct($message);
	}

}
