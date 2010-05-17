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
	 * Set up the various views: the template is at the top, defining the overall
	 * HTML page; then the controllerView is put within that (for layout that is
	 * common to all of a controller's actions); and lastly comes the action's view
	 * object.
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
				'edit' => 'View &amp; Edit',
				'import' => 'Import',
				'export' => 'Export',
				'calendar' => 'Calendar',
				'map' => 'Map'
		);

		// Databases
		$this->dbms = new Webdb_DBMS;
		$this->template->databases = $this->dbms->list_dbs();
		$this->_set_database();
		$this->_set_table();

	} // _before()

	private function _set_database()
	{
		$this->database = $this->dbms->get_database();
		$this->template->database = $this->database;
		$this->view->database = $this->database;
		if (!$this->database)
		{
			$this->add_template_message(
					'Please select a database from the tabs above.',
					'info'
			);
		}
	} // _set_database()

	private function _set_table()
	{
		if ($this->database)
		{
			$this->table = $this->database->get_table();
			$this->template->tables = $this->database->list_tables();
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
				'status'=>$status,
				'message'=>$message
		);
	}

	/**
	 *
	 * @param <type> $dbname
	 * @param <type> $tablename
	 */
	public function action_index()
	{
	}

	public function action_edit()
	{
		$id = $this->request->param('id');
		$this->view->columns = $this->table->get_columns();
		$rows = $this->table->get_rows($id);
		$this->view->row = $rows[0];
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

}