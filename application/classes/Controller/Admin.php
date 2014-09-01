<?php

class Controller_Admin extends Controller_Base {

	public function action_admin()
	{
		
	}

	public function action_install()
	{
		if ( ! $this->request->post('install'))
		{
			return;
		}
		$tables = Database::instance()->list_tables('settings');
		//echo Debug::vars($tables);
		if ( ! in_array('settings', $tables))
		{
			$sql = 'CREATE TABLE settings ('
				.' `id` int(4) NOT NULL AUTO_INCREMENT,'
				.' `name` varchar(65) NOT NULL,'
				.' `value` text NOT NULL,'
				.' PRIMARY KEY (`id`),'
				.' UNIQUE KEY `name` (`name`)'
				.')';
			DB::query(NULL, $sql)->execute();
		}
		$hasSiteTitle = DB::query(Database::SELECT, "SELECT COUNT(id) AS count FROM settings WHERE name='site_title';")
			->execute()
			->current();
		if ($hasSiteTitle['count'] == 0)
		{
			$siteTitle = Kohana::$config->load('webdb')->get('site_title');
			$sql = "INSERT INTO settings SET name='site_title', value='$siteTitle'";
			DB::query(Database::INSERT, $sql)->execute();
		}
	}

}
