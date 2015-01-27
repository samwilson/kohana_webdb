<?php

class Controller_User extends Controller_Base {

	public function action_login()
	{
		$this->view->username = '';
		$this->view->return_to = Arr::get($_REQUEST, 'return_to', '');
		$this->view->register = WebDB::config('auth') == 'WebDB';
		if ($this->request->post('login') !== NULL)
		{
			Auth::instance()->logout(); // Just in case we're logged in.
			$this->view->username = trim($this->request->post('username'));
			$password = trim($this->request->post('password'));
			Auth::instance()->login($this->view->username, $password);
			if (Auth::instance()->logged_in())
			{
				try
				{
					$user = new WebDB_User($this->view->username);
					$user->log_in();
					$this->add_flash_message('You are now logged in.', 'info');
					Kohana::$log->add(Kohana_Log::INFO, $this->view->username.' logged in.');
				} catch (Exception $e)
				{
					$msg = 'Unable to log in as :username.';
					throw HTTP_Exception::factory(500, $msg, array(':username' => $this->view->username), $e);
				}
				$this->redirect($this->view->return_to);
			} else
			{
				Kohana::$log->add(Kohana_Log::INFO, 'Failed log in: '.$this->view->username);
				$this->add_template_message('Login failed.  Please try again.');
			}
		} // if ($this->request->post('login') !== NULL)
	}

	public function action_logout()
	{
		if (Auth::instance()->logged_in())
		{
			$log_message = Auth::instance()->get_user().' logged out.';
			Kohana::$log->add(Kohana_Log::INFO, $log_message);
			Auth::instance()->logout();
			Session::instance()->destroy();
			$this->add_flash_message('You are now logged out.', 'info');
		} else
		{
			$this->add_flash_message('You were not logged in.', 'notice');
		}
		$this->redirect(Route::get('user')->uri(array('action'=>'login')));
	}

	public function action_profile()
	{
		$username = $this->request->param('username');
		$user = new WebDB_User($username);
		$this->view->user = $user;
	}

}
