<?php

class WebDB_User {

	protected $id;
	protected $username;

	public function __construct($username)
	{
		$this->username = $username;
		$data = DB::select('id')
			->from('users')
			->where('username', 'LIKE', $username)
			->execute()
			->current();
		if ($data)
		{
			$this->id = $data['id'];
		}
	}

	public function get_id()
	{
		return $this->id;
	}

	public function get_username()
	{
		return $this->username;
	}

	public function get_roles()
	{
		$roles = DB::select(array('roles.name'))
			->from('user_roles')
			->join('users')->on('user_id', '=', 'users.id')
			->join('roles')->on('role_id', '=', 'roles.id')
			->where('username', '=', $this->get_username())
			->execute()
			->current();
		return $roles;
	}

	public function has_role($role)
	{
		$exists = DB::select(array(DB::expr('COUNT(*)'), 'count'))
			->from('user_roles')
			->join('users')->on('user_id', '=', 'users.id')
			->join('roles')->on('role_id', '=', 'roles.id')
			->where('username', '=', $this->username)
			->where('roles.name', '=', $role)
			->execute()
			->current();
		return $exists['count'] > 0;
	}

	public function log_in()
	{
		$user_id = $this->add_if_missing('users', 'username', $this->username);
		$auth = Auth::instance();
		if (method_exists($auth, 'get_roles'))
		{
			foreach ($auth->get_roles() as $role)
			{
				$role_id = $this->add_if_missing('roles', 'name', $role);

				$user_role_exists = DB::select(array(DB::expr('COUNT(*)'), 'count'))
					->from('user_roles')
					->where('user_id', '=', $user_id)
					->where('role_id', '=', $role_id)
					->execute()
					->current();
				if ($user_role_exists['count'] == 0)
				{
					DB::insert('user_roles', array('user_id', 'role_id'))
						->values(array($user_id, $role_id))
						->execute();
				}
			}
		}
	}

	/**
	 * Create a record, if it doesn't already exist.
	 * @return integer The ID of the record
	 */
	protected function add_if_missing($table, $field, $value)
	{
		$record = DB::select('id', $field)
			->from($table)
			->where($field, '=', $value)
			->execute()
			->current();
		if (isset($record['id']))
		{
			return $record['id'];
		}
		$new = DB::insert($table)
			->columns(array($field))
			->values(array($value))
			->execute();
		return $new[0];
	}

}
