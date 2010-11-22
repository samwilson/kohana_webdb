<?php defined('SYSPATH') or die('No direct script access.');
/**
 * WebDB controller, the entry point into WebDB, and from where all other things
 * are coordinated.
 *
 * @package  WebDB
 * @category Controller
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

		if ($this->request->action == 'media')
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
			'export' => 'Export',
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

		$this->_query_string_session();

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

	/**
	 * Save and load query string (i.e. `$_GET`) variables from the `$_SESSION`.
	 * The idea is to carry query string variables between requests, even
	 * when those variables have been omitted in the URI.
	 *
	 * 1. If a request has query string parameters, they are saved to
	 *    `$_SESSION['qs']`, merging with whatever is already there.
	 * 2. If there are parameters saved in `$_SESSION['qs']`, and if they're
	 *    not already in the query string, add them and redirect the request to
	 *    the resulting URI.
	 *
	 * @return void
	 */
	private function _query_string_session()
	{
		// Only save these keys.
		$to_save = array('filters','orderby','orderdir');

		// Save the query string, adding to what's already saved.
		if (count($_GET)>0)
		{
			$session = (isset($_SESSION['qs'])) ? $_SESSION['qs'] : array();
			foreach ($_GET as $key=>$val)
			{
				// Merge non-empty variables only.
				if ((empty($val) && isset($_SESSION['qs'][$key])) || (!in_array($key, $to_save))) {
					unset($_SESSION['qs'][$key]);
					continue;
				}
				$session[$key] = $val;
			}
			$_SESSION['qs'] = $session;
		}

		// Load query string variables, unless they're already present.
		if (isset($_SESSION['qs']) && count($_SESSION['qs'])>0)
		{
			$has_new = FALSE; // Whether there's anything in SESSION that's not in GET
			foreach ($_SESSION['qs'] as $key=>$val)
			{
				if (!isset($_GET[$key]) && in_array($key, $to_save))
				{
					$_GET[$key] = $val;
					$has_new = TRUE;
				}
			}
			// Don't redirect for POST requests.
			if ($has_new && $_SERVER['REQUEST_METHOD']=='GET')
			{
				$query = URL::query($_SESSION['qs']);
				$_SESSION['qs'] = array();
				$uri = URL::base(FALSE, TRUE).$this->request->uri.$query;
				$this->request->redirect($uri);
			}
		}
	}

	private function _set_table()
	{
		if ($this->database)
		{
			$this->table = $this->database->get_table();
			/*
			// Divide tables by editability
			$this->template->tables = array('data_entry'=>array(),'reference'=>array());
			foreach ($this->database->get_tables() as $table) {
				if ($table->can('update') || $table->can('insert')) {
					$this->template->tables['data_entry'][$table->get_name()] = $table;
				} else {
					$this->template->tables['reference'][$table->get_name()] = $table;
				}
			}*/
			$this->template->tables = $this->database->get_tables(TRUE);
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
	 *
	 * @return void (Does not return)
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
			$row['label'] = $this->table->get_title($row['id']);
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
	 * Export the current table with the current filters applied.
	 * Filters are passed as $_GET parameters, just as for the
	 * [index](api/Controller_WebDB#action_index) action.
	 *
	 * Each field is constructed from the standard field view, which is then
	 * tag-stripped and trimmed.  This makes this action nice and simple, but
	 * there may be some unforeseen issues; we'll see how things go.
	 *
	 * @return void
	 */
	public function action_export()
	{
		// Add filters.
		$this->table->add_GET_filters();

		// Create temp CSV file.
        $tmp_file = sys_get_temp_dir().DIRECTORY_SEPARATOR.md5(time()).'.csv';
        $file = fopen($tmp_file, 'w');

		// Add the column headers to the file.
		$column_names = array_keys($this->table->get_columns());
        $headers = Webdb_Text::titlecase($column_names);
        fputcsv($file, $headers);

		// Write all the data.
        foreach ($this->table->get_rows(FALSE) as $row) {
            $line = array(); // The line to write to CSV.
            foreach ($this->table->get_columns() as $column) {
				$edit = FALSE;
				$new_row_ident_label = '';
				$field = View::factory('webdb/field')
					->bind('column', $column)
					->bind('row', $row)
					->bind('edit', $edit)
					->bind('new_row_ident', $new_row_ident_label)
					->render();
                $line[] = trim(strip_tags(trim($field)));
            }
            fputcsv($file, $line);
        }

        // Send file to browser.
		$this->request->response = file_get_contents($tmp_file);
        $filename = date('Y-m-d').'_'.$this->table->get_name().'.csv';
        $this->request->send_file(TRUE, $filename);

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

		$this->table->add_GET_filters();
		$this->view->filters = $this->table->get_filters();

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
	public function action_media()
	{
		// Get the file path from the request
		$file = $this->request->param('file');

		// Find the file extension
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		// Remove the extension from the filename
		$file = substr($file, 0, -(strlen($ext) + 1));

		$filename = Kohana::find_file('media/webdb', $file, $ext);
		if ($filename)
		{

			// Send the file content as the response
			$this->auto_render = FALSE;
			$this->request->response = file_get_contents($filename);

			// Set the proper headers to allow caching
			$this->request->headers['Content-Type']   = File::mime_by_ext($ext);
			$this->request->headers['Content-Length'] = filesize($filename);
			$this->request->headers['Last-Modified']  = date('r', filemtime($filename));
		}
		else
		{
			// Return a 404 status
			$this->request->status = 404;
			$this->request->headers['Content-Type'] = 'text/plain';
			$this->template = '404 Not Found';
		}

	}

	public function action_login()
	{
		$this->template->set_global('database', FALSE);
		$this->template->set_global('table', FALSE);
		$this->template->set_global('databases', array());
		$this->template->set_global('tables', array());
		$this->view->return_to = (isset($_REQUEST['return_to'])) ? $_REQUEST['return_to'] : URL::site('webdb');
		if (isset($_POST['login']))
		{
			$post = Validate::factory($_POST)
				->filter(TRUE, 'trim')
				->rule('username', 'not_empty')
				->rule('username', 'min_length', array(1));
				//->rule('password', 'not_empty');
			if($post->check())
			{
				$username = $post['username'];
				$password = Arr::get($post, 'password', '');
				try
				{
					if (Auth::instance()->login($username, $password))
					{
						$this->request->redirect($this->view->return_to);
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