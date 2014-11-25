<?php

class Auth_WebDB extends Auth {

	protected function _login($username, $password, $remember)
	{
		$passwordHasher = new \Hautelook\Phpass\PasswordHash(8, false);

		$password = $passwordHasher->HashPassword('secret');
		var_dump($password);

		$passwordMatch = $passwordHasher->CheckPassword('secret', "$2a$08$0RK6Yw6j9kSIXrrEOc3dwuDPQuT78HgR0S3/ghOFDEpOGpOkARoSu");
		var_dump($passwordMatch);
	}

	public function password($username)
	{
		
	}

	public function check_password($user)
	{
		
	}

}
