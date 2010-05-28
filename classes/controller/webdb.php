<?php defined('SYSPATH') or die('No direct script access.');

class Controller_WebDB extends Controller_Template
{

	/** @var View The view object that controller actions add data to. */
	protected $view;

	/** @var Webdb_DBMS */
	protected $dbms;

	/** @var Webdb_DBMS_Database The current database. */
	protected $database;

	/** @var Webdb_DBMS_Table The current table. */
	protected $table;

	/**
	 * Set up the various views: a site-wide template; and a per-action view.
	 * Also deal with selecting (or issuing messages to the user) the current
	 * database and table.
	 *
	 * @return void
	 */
	public function before()
	{
		parent::before();

		if ($this->request->action == 'resources')
		{
			return;
		}

		// Set up views
		$this->view = View::factory($this->request->action);
		$this->template->messages = array();
		$this->template->content = $this->view;
		$this->template->controller = $this->request->controller;
		$this->template->action = $this->request->action;
		$this->template->actions = array(
			'index' => 'Browse &amp; Search',
			'edit' => 'View/Edit',
			'import' => 'Import',
			//'export' => 'Export',
			//'calendar' => 'Calendar',
			//'map' => 'Map',
		);

		/*
		 * User authentication
		 */
		//auth::instance()->logged_in();

		/*
		 * Database & table.
		 * Do not instantiate database for resources, login, or logout actions.
		*/
		if ($this->request->action !== 'login')
		{
			try
			{
				$this->dbms = new Webdb_DBMS;
			} catch (Webdb_DBMS_ConnectionException $e)
			{
				$this->add_flash_message($e->getMessage());
				$this->request->redirect('webdb/login');
			}
			$this->template->databases = $this->dbms->list_dbs();
			$this->_set_database();
			$this->_set_table();
		}

		/*
		 * Add flash messages to the template, then clear them from the session.
		*/
		foreach (session::instance()->get('flash_messages', array()) as $msg)
		{
			$this->add_template_message($msg['message'], $msg['status']);
		}
		session::instance()->set('flash_messages', array());

	} // _before()

	private function _set_database()
	{
		$this->database = $this->dbms->get_database();
		if (!$this->database)
		{
			$this->add_template_message(
				'Please select a database from the tabs above.',
				'info'
			);
		}
		$this->template->set_global('database', $this->database);
	} // _set_database()

	private function _set_table()
	{
		if ($this->database)
		{
			$this->table = $this->database->get_table();
			$this->template->tables = $this->database->get_tables();
			if (!$this->table)
			{
				$this->add_template_message(
					'Please select a table from the menu to the left.',
					'info'
				);
			}
		} else
		{
			$this->table = FALSE;
			$this->template->tables = array();
		}
		$this->template->set_global('table', $this->table);
	}

	protected function add_template_message($message, $status = 'notice')
	{
		$this->template->messages[] = array(
			'status'  => $status,
			'message' => $message
		);
	}

	protected function add_flash_message($message, $status = 'notice')
	{
		$flash_messages = session::instance()->get('flash_messages', array());
		$flash_messages[] = array(
			'status'=>$status,
			'message'=>$message
		);
		session::instance()->set('flash_messages', $flash_messages);
	}

	/**
	 *
	 * @param <type> $dbname
	 * @param <type> $tablename
	 */
	public function action_index()
	{
		//auth::instance()->logged_in();
		//echo '<pre>';
		//print_r(auth::instance()->get_user());
		//exit();
	}

	public function action_edit()
	{
		$id = $this->request->param('id');
		$this->view->columns = $this->table->get_columns();
		if ($id)
		{
			//$rows = $this->table->get_rows($id);
			$this->view->row = $this->table->get_row($id);
		} else
		{
			$this->view->row = $this->table->get_default_row();
		}
	}

	public function action_import($database, $table)
	{
		$this->view->errors = FALSE;
		$this->view->stages = array('choose_file', 'match_fields', 'preview', 'complete_import');
		$this->view->stage = 'choose_file';

		if (arr::get($_POST, 'upload', FALSE))
		{
			$validation = Validate::factory($_FILES, 'uploads');
			$validation->rule('file','upload::not_empty')->rule('file','upload::type',array(array('csv')));
			if ($validation->check())
			{
				$this->view->stage = 'match_fields';
				// Upload::save($_FILES['file']);
			} else
			{
				foreach ($validation->errors() as $err)
				{
					switch($err[0])
					{
						case 'upload::not_empty':
							$this->add_template_message('You did not choose a file to upload!');
							break;
						case 'upload::type':
							$this->add_template_message('The file that you uploaded is not of required type.');
							break;
						default:
							$this->add_template_message('An error occured.<br /><pre>'.kohana::dump($err).'</pre>');
					}
				}
			}
		}
	}

	/**
	 * Serve up static resource files such as CSS, Javascript, or images. This
	 * controller copied from the userguide and modified.
	 *
	 * @link http://github.com/kohana/userguide/blob/19da863d48a995eb79cc2b67fd9705b17f2f2451/classes/controller/userguide.php#L166
	 * @return void
	 */
	public function action_resources()
	{
		// Get the file path from the request
		$file = $this->request->param('file');

		// Find the file extension
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		// Remove the extension from the filename
		$file = substr($file, 0, -(strlen($ext) + 1));

		$file = Kohana::find_file('resources', $file, $ext);
		if ($file)
		{

			// Send the file content as the response
			$this->auto_render = FALSE;
			$this->request->response = file_get_contents($file);

			// Set the proper headers to allow caching
			$this->request->headers['Content-Type']   = File::mime_by_ext($ext);
			$this->request->headers['Content-Length'] = filesize($file);
			$this->request->headers['Last-Modified']  = date('r', filemtime($file));
		}
		else
		{
			// Return a 404 status
			$this->request->status = 404;
			$this->template->messages[] = array('message'=>'Not Found','status'=>'error');
		}

	}

	public function action_login()
	{
		$this->template->set_global('database', FALSE);
		$this->template->set_global('table', FALSE);
		$this->template->set_global('databases', array('Login'));
		$this->template->set_global('tables', array());
		if (isset($_POST['login']))
		{
			$post = Validate::factory($_POST)
				->filter(TRUE,'trim')
				->rule('username', 'not_empty')
				->rule('username', 'min_length', array(1))
				->rule('password', 'not_empty');
			if($post->check())
			{
				$username = $post['username'];
				$password = $post['password'];
				try
				{
					if (Auth::instance()->login($username, $password))
					{
						$this->request->redirect('webdb');
					} else
					{
						$this->add_template_message('Login failed.  Please try again.');
					}
				} catch (Exception $e)
				{
					$this->add_template_message($e->getMessage());
				}
			} else
			{
				$this->add_template_message('You must enter both your username and password.');
			}
		}
	}

	public function action_logout()
	{
		auth::instance()->logout();
		$this->add_flash_message('You are now logged out.', 'info');
		$this->request->redirect('webdb');
	}

}