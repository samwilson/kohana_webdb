<?php defined('SYSPATH') or die('No direct script access.');
/**
 * WebDB controller, the entry point into WebDB, and from where all other things
 * are coordinated.
 *
 * @package  WebDB
 * @category Base
 * @author   Sam Wilson
 * @license  Simplified BSD License
 * @link     http://github.com/samwilson/kohana_webdb
 */
class Controller_WebDB extends Controller_Template
{

	/**
	 * @var Webdb_DBMS_Database The current database.
	 */
	protected $database;

	/**
	 * @var Webdb_DBMS
	 */
	protected $dbms;

	/**
	 * Included here for the benefit of phpDoc autocompletion (in Netbeans).
	 * @var Request The Request that created this controller.
	 */
	public $request;

	/**
	 * @var Webdb_DBMS_Table The current table.
	 */
	protected $table;

	/**
	 * @var View The site-wide WebDB page template.
	 */
	public $template = 'webdb/template';

	/**
	 * @var View The view object that controller actions add data to.
	 */
	protected $view;

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
		if (Kohana::find_file('views/webdb', $this->request->action))
		{
			$this->view = View::factory('webdb/'.$this->request->action);
			$this->template->content = $this->view;
		}
		$this->template->messages = array();
		$this->template->controller = $this->request->controller;
		$this->template->action = $this->request->action;
		$this->template->actions = array(
			'index'  => 'Browse &amp; Search',
			'edit'   => 'New',
			'import' => 'Import',
			//'export' => 'Export',
			//'calendar' => 'Calendar',
			//'map' => 'Map',
		);

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
			// Divide tables by editability
			$this->template->tables = array('data_entry'=>array(),'reference'=>array());
			foreach ($this->database->get_tables() as $table) {
				if ($table->can('update') || $table->can('insert')) {
					$this->template->tables['data_entry'][$table->get_name()] = $table;
				} else {
					$this->template->tables['reference'][$table->get_name()] = $table;
				}
			}
			//$this->template->tables = $this->database->get_tables();
			if (!$this->table && count($this->database->get_tables()) > 0)
			{
				$this->add_template_message(
					'Please select a table from the menu to the left.',
					'info'
				);
			} elseif (!$this->table && count($this->database->get_tables()) == 0)
			{
				$this->add_template_message(
					'You do not have permission to view any tables in this database.',
					'notice'
				);
			}
		} else
		{
			$this->table = FALSE;
			$this->template->tables = array();
		}
		$this->template->set_global('table', $this->table);
	}
	
	/**
	 * Output JSON data for the current table, for use in autocomplete inputs.
	 */
	public function action_autocomplete()
	{
		$title_column_name = $this->table->get_title_column()->get_name();
		if (isset($_GET['term']))
		{
			$this->table->reset_filters();
			$this->table->add_filter($title_column_name, 'like', $_GET['term']);
		}
		$json_data = array();
		foreach ($this->table->get_rows(FALSE) as $row)
		{
			$row['label'] = $row[$title_column_name];
			$json_data[] = $row;
		}
		exit(json_encode($json_data));
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

	public function action_edit()
	{
		$id = $this->request->param('id');
		$this->view->table = $this->table;
		//$this->view->columns = $this->table->get_columns();

		/*
		 * Save submitted data.
		*/
		if (isset($_POST['save']))
		{
			// Get row
			$row = array_shift($_POST['data']);
			// Assume unset (i.e. unsent) checkboxes are unchecked.
			foreach ($this->table->get_columns() as $column_name=>$column)
			{
				if ($column->get_type() == 'int' && $column->get_size() == 1 && !isset($row[$column_name]))
				{
					$row[$column_name] = 0;
				}
			}
			// Save row
			$id = $this->table->save_row($row);
			$this->add_template_message('Record saved.', 'info');
		}

		/*
		 * Get data to populate edit form (or give message why not).
		*/
		if ($id)
		{
			$this->template->actions['edit'] = "Edit";
			$this->view->row = $this->table->get_row($id);
		} else
		{
			if (!$this->table->can('insert'))
			{
				$this->add_template_message('You do not have permission to add a new record to this table.');
				return;
			}
			// Get default data from the database and HTTP request.
			$this->view->row = array_merge($this->table->get_default_row(), $_GET);
		}
	}

	/**
	 * This action is for importing a single CSV file into a single database table.
	 * It guides the user through the four stages of importing:
	 * uploading, field matching, previewing, and doing the actual import.
	 * All of the actual work is done in [WebDB_File_CSV].
	 *
	 * 1. In the first stage, a CSV file is **uploaded**, validated, and moved to a temporary directory.
	 *    The file is then accessed from this location in the subsequent stages of importing,
	 *    and only deleted upon either successful import or the user cancelling the process.
	 *    (The 'id' parameter of this action is the identifier for the uploaded file.)
	 * 2. Once a valid CSV file has been uploaded,
	 *    its colums are presented to the user to be **matched** to those in the database table.
	 *    The columns from the database are presented first and the CSV columns are matched to these,
	 *    rather than vice versa,
	 *    because this way the user sees immediately what columns are available to be imported into.
	 * 3. The column matches are then used to produce a **preview** of what will be added to and/or changed in the database.
	 *    All columns from the database are shown (regardless of whether they were in the import) and all rows of the import.
	 *    If a column is not present in the import the database will (obviously) use the default value if there is one;
	 *    this will be shown in the preview.
	 * 4. When the user accepts the preview, the actual **import** of data is carried out.
	 *    Rows are saved to the database using the usual [WebDB_DBMS_Table::save()](api/Webdb_DBMS_Table#save_row),
	 *    and a message presented to the user to indicate successful completion.
	 *
	 * @return void
	 */
	public function action_import()
	{

		// First make sure the user is allowed to import data into this table.
		if (!$this->table->can('import'))
		{
			$this->template->content = '';
			$this->add_template_message('You do not have permission to import data into this table.');
			return;
		}

		// Set up the progress bar.
		$this->view->stages = array('choose_file', 'match_fields', 'preview', 'complete_import');

		// Stage 1: Uploading
		$this->view->stage = $this->view->stages[0];
		try
		{
			$this->view->file = new Webdb_File_CSV;
		} catch (Kohana_Exception $e)
		{
			$this->add_template_message($e->getMessage());
			return;
		}
		if ($this->view->file->loaded() && !$this->request->param('id'))
		{
			$url = 'webdb/import/'.$this->database->get_name().'/'.$this->table->get_name().'/'.$this->view->file->hash;
			$this->request->redirect($url);
		}

		// Stage 2: Matching fields
		if ($this->view->file->loaded())
		{
			$this->view->stage = $this->view->stages[1];
		}

		// Stage 3: Previewing
		if ($this->view->file->loaded() && isset($_POST['columns']))
		{
			$this->view->file->match_fields($this->table, $_POST['columns']);
			$this->view->stage = $this->view->stages[2];
		}

		// Stage 4: Import
		if ($this->view->file->loaded() && isset($_POST['data']))
		{
			//exit(Kohana::debug($_POST['data']));
			$this->view->stage = $this->view->stages[3];
			foreach ($_POST['data'] as $row)
			{
				$this->table->save_row($row);
			}
			$this->add_template_message('Import complete; '.count($_POST['data']).' rows inserted and/or updated.', 'info');
		}

	}

	/**
	 *
	 * @param <type> $dbname
	 * @param <type> $tablename
	 * @return void
	 */
	public function action_index()
	{
		$this->view->columns = array();
		$this->view->filters = array();
		if (!$this->table) {
			return;
		}
		// The permitted filter operators.
		$this->view->operators = $this->table->get_operators();
		foreach ($this->table->get_columns() as $col) {
			$this->view->columns[$col->get_name()] = Webdb_Text::titlecase($col->get_name());
		}

		// Get filters from GET and SESSION, then delete those in SESSION (we'll
		// recreate them in a moment).
		//$session = Session::instance();
		$filters = Arr::get($_GET, 'filters', array()); // + $session->get('webdb_filters', array());
		//$session->set('webdb_filters', array());
		foreach ($filters as $filter) {
			$column = arr::get($filter, 'column', FALSE);
			$operator = arr::get($filter, 'operator', FALSE);
			$value = arr::get($filter, 'value', FALSE);
			$this->table->add_filter($column, $operator, $value);
		}
		$this->view->filters = $this->table->get_filters();
		//$session->set('webdb_filters', $this->view->filters);

		// Add new filter
		$this->view->filters[] = array(
			'column' => $this->table->get_title_column()->get_name(),
			'operator' => 'like',
			'value' => ''
		);
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
		$this->template->set_global('databases', array('Log in'));
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
						//exit(__FILE__.__LINE__);
						$this->request->redirect('webdb');
					} else
					{
						$this->add_template_message('Login failed.  Please try again.');
					}
				} catch (Exception $e)
				{
					//exit(__FILE__.__LINE__);
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