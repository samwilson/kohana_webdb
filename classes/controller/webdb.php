<?php defined('SYSPATH') or die('No direct script access.');

class Controller_WebDB extends Controller_Template
{

	protected $db;


	public function before()
	{
		parent::before();
		$this->db = Database::instance();
		$this->template->databases = $this->db->query(Database::SELECT, "SHOW DATABASES", true);
		$this->template->tables = array();
	}

	public function action_index($dbname = false)
	{
		if ($dbname)
		{
			$this->get_tables($dbname);
		}
	}

	public function action_browse($dbname = false, $tablename = false)
	{
		if (!$tablename)
		{
			$this->request->redirect("/webdb/index/$dbname", 300);
		}
		if ($dbname)
		{
			$this->get_tables($dbname);
		}
	}

	protected function get_tables($dbname)
	{
		$this->template->database = $dbname;
		$config = Kohana::config('database')->default;
		$config['connection']['database'] = $dbname;
		//echo '<pre>'.kohana::dump($config).'</pre>';
		$this->db = Database::instance(null, $config);
		$this->template->tables = $this->db->list_tables();
		return $this->template->tables;
	}

}