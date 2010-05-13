<?php defined('SYSPATH') or die('No direct script access.');

class Controller_WebDB extends Controller_Template
{

	/** @var View The view object that controller actions add data to. */
	protected $view;

	/** @var Webdb_DBMS */
	protected $dbms;

	/** @var Database */
	protected $db;

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
			'view' => 'View &amp; Edit'
		);

		// Databases
		$this->dbms = new Webdb_DBMS;
		$this->template->databases = $this->dbms->list_dbs();
		$this->_set_database();
		$this->_set_table();

	} // _before()

	private function _set_database()
	{
		$this->db = $this->dbms->get_database();
		$this->template->database = $this->db;
		$this->view->database = $this->db;
		if (!$this->db)
		{
			$this->add_template_message(
				'Please select a database from the tabs above.',
				'info'
			);
		}
	} // _set_database()

	private function _set_table()
	{
		if ($this->db)
		{
			$this->table = $this->db->get_table();
			$this->template->tables = $this->db->list_tables();
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
		$this->template->table = $this->table;
		$this->view->table = $this->table;
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
//		$this->view->table = false;
//		//$this->view->columns = array();
//		//$this->view->rows = array();
//		//$this->view->pagination_links = false;
//		if ($this->table)
//		{
//			$pagination = $this->table->get_pagination();
//
//			// Rows
//			$query = DB
//			$query->from($this->table->get_name());
//			$
//			$query->offset($pagination->offset);
//			$query->limit($pagination->items_per_page);
//
//			$this->view->pagination_links = $pagination->render();
//			$this->view->rows = $query->execute();


		/*
			// Columns
			$this->view->columns = $this->db->list_columns($tablename);

			// Rows
			$query = DB::select();
			$query->from($tablename);

			// Pagination
			$total_row_count = Database::instance()->count_records($tablename);
			$pagination = new Pagination(array('total_items' => $total_row_count));
			$this->view->pagination_links = $pagination->render();
			$query->offset($pagination->offset);
			$query->limit($pagination->items_per_page);
			$this->view->rows = $query->execute();
		*/
		//}
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