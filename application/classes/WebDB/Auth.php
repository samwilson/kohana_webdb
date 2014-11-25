<?php

class WebDB_Auth {

	public static function logged_in($role = NULL)
	{
		$logged_in = Auth::instance()->logged_in();
		// Check for WebDB-specific roles
		if ( ! is_null($role))
		{
			$user = new WebDB_User(Auth::instance()->get_user());
			return $user->has_role($role);
		}
		return $logged_in;
	}

}
