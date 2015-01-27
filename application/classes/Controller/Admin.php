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
		$tables = Database::instance()->list_tables();
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
		if ( ! in_array('users', $tables))
		{
			$sql = 'CREATE TABLE users ('
				.' `id` int(4) NOT NULL AUTO_INCREMENT,'
				.' `username` varchar(65) NOT NULL,'
				.' PRIMARY KEY (`id`),'
				.' UNIQUE KEY `username` (`username`)'
				.')';
			DB::query(NULL, $sql)->execute();
		}
		if ( ! in_array('roles', $tables))
		{
			$sql = 'CREATE TABLE roles ('
				.' `id` INT(4) NOT NULL AUTO_INCREMENT,'
				.' `name` varchar(65) NOT NULL,'
				.' PRIMARY KEY (`id`),'
				.' UNIQUE KEY `name` (`name`)'
				.')';
			DB::query(NULL, $sql)->execute();
		}
		if ( ! in_array('user_roles', $tables))
		{
			$sql = 'CREATE TABLE user_roles ('
				.' user_id INT(6) NOT NULL,'
				.' role_id INT(4) NOT NULL,'
				.' PRIMARY KEY (user_id, role_id)'
				.')';
			DB::query(NULL, $sql)->execute();
		}
		if ( ! DB::query(NULL, "SELECT TRUE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
				WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'user_roles_user'")->execute())
		{
			$sql = 'ALTER TABLE user_roles'
				.' ADD CONSTRAINT `user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE';
			DB::query(NULL, $sql)->execute();
		}
		if ( ! DB::query(NULL, "SELECT TRUE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
				WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'user_roles_role'")->execute())
		{
			$sql = 'ALTER TABLE user_roles'
				.' ADD CONSTRAINT `user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE';
			DB::query(NULL, $sql)->execute();
		}
		if ( ! in_array('permissions', $tables))
		{
			$sql = 'CREATE TABLE permissions ('
				.' `id` int(4) NOT NULL AUTO_INCREMENT,'
				.' `table_name` varchar(65) NOT NULL DEFAULT "*",'
				.' `column_name` varchar(65) NOT NULL DEFAULT "*",'
				.' `role_id` INT(4) NOT NULL,'
				.' `activity_name` varchar(65) NOT NULL DEFAULT "*",'
				.' PRIMARY KEY (`id`)'
				.')';
			DB::query(NULL, $sql)->execute();
		}
		if ( ! DB::query(NULL, "SHOW INDEXES FROM `permissions` WHERE Column_name='role_id'")->execute())
		{
			$sql = 'ALTER TABLE permissions'
				.' ADD CONSTRAINT `permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE';
			DB::query(NULL, $sql)->execute();
		}
//		$hasSiteTitle = DB::query(Database::SELECT, "SELECT COUNT(id) AS count FROM settings WHERE name='site_title';")
//			->execute()
//			->current();
//		if ($hasSiteTitle['count'] == 0)
//		{
//			$siteTitle = Kohana::$config->load('webdb')->get('site_title');
//			$sql = "INSERT INTO settings SET name='site_title', value='$siteTitle'";
//			DB::query(Database::INSERT, $sql)->execute();
//		}

		Cache::instance()->delete_all();
		$this->add_flash_message('Installation or upgrade has completed sucessfully.', 'info');
		$this->redirect(Route::get('default')->uri());
	}

	public function action_purge()
	{
		Cache::instance()->delete_all();
		$this->add_template_message('Cache cleared.', 'info');
		$this->template->content = '';
	}
}
