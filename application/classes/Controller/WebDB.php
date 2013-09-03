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
class Controller_WebDB extends Controller_Base
{

	/**
	 * @var WebDB_DBMS_Database The current database.
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

		$this->dbms = new WebDB_DBMS;
		try
		{
			$this->dbms->connect();
			$this->template->databases = $this->dbms->list_dbs();
			$this->_set_database();
			$this->_set_table();
		} catch (Database_Exception $e)
		{
			if (!Auth::instance()->logged_in())
			{
				$this->add_flash_message('Unable to connect.  Please log in.');
				$this->redirect('login');
			}
			$this->template->databases = array();
			$this->add_template_message('Initialisation error: '.$e->getMessage());
		}

	} // _before()

	/**
	 * Set the current database name.
	 */
	private function _set_database()
	{
		try
		{
			$this->database = $this->dbms->get_database();
		} catch (Exception $e)
		{
			$this->add_template_message($e->getMessage(), 'notice');
		}
		if (!$this->database)
		{
			$message = (count($this->dbms->list_dbs())>0)
				? 'Please select a database from the tabs above.'	
				: 'No databases are available.';
			$this->add_template_message($message, 'info');
			// If only one DB, redirect to that one.
			$dbs = $this->dbms->list_dbs();
			if (count($dbs)==1)
			{
				//$this->request->redirect('index/'.$dbs[0]);
			}
		}
		$this->template->set_global('database', $this->database);
		return TRUE;
	} // _set_database()

	private function _set_table()
	{
		if ($this->database)
		{
			$this->template->tables = $this->database->get_tables(TRUE);
			try
			{
				$this->table = $this->database->get_table();
			} catch (Exception $e)
			{
				$this->add_template_message($e->getMessage(), 'notice');
			}
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
		} 
//		else
//		{
//			$this->table = FALSE;
//			$this->template->tables = array();
//		}
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
		$pk_column_name = $this->table->get_pk_column()->get_name();
		if (isset($_GET['term']))
		{
			$this->table->reset_filters();
			$this->table->add_filter($title_column_name, 'like', $_GET['term']);
		}
		elseif (isset($_GET[$pk_column_name]))
		{
			$this->table->reset_filters();
			$this->table->add_filter($pk_column_name, '=', $_GET[$pk_column_name]);
		}
		$json_data = array();
		foreach ($this->table->get_rows(TRUE, FALSE) as $row)
		{
			$row['label'] = $row['webdb_title']; //$this->table->get_title($row['id']);
			$json_data[] = $row;
		}
		exit(json_encode($json_data));
	}

	public function action_edit()
	{
		if (!$this->table) {
			$this->template->content = null;
			return;
		}
		$id = $this->request->param('id');
		$this->view->table = $this->table;

		/*
		 * Save submitted data.
		*/
		if (isset($_POST['save']))
		{
			// Get row (the first element of $_POST.
			$row = array_shift($_POST['data']);
			// Assume unset (i.e. unsent) checkboxes are unchecked.
			foreach ($this->table->get_columns() as $column_name=>$column)
			{
				if ($column->is_boolean() AND !isset($row[$column_name]))
				{
					$row[$column_name] = 0;
				}
			}
			// Save row
			$id = $this->table->save_row($row);
			if (!empty($id)) {
				$this->add_flash_message('Record saved.', 'info');
				$url = 'edit/'.$this->database->get_name().'/'.$this->table->get_name().'/'.$id;
				$this->redirect($url);
			}
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
				$this->template->content = null;
				return;
			}
			// Get default data from the database and HTTP request.
			$this->view->row = array_merge($this->table->get_default_row(), $_GET);
		}
	}

	/**
	 * Export the current table with the current filters applied.
	 * Filters are passed as $_GET parameters, just as for the index action.
	 *
	 * Each field is constructed from the standard field view, which is then
	 * tag-stripped and trimmed.  This makes this action nice and simple, but
	 * there may be some unforeseen issues; we'll see how things go.
	 *
	 * @return void
	 */
	public function action_export()
	{
		$this->response->headers('Content-Encoding', 'UTF-8');
		$this->response->headers('Content-type', 'text/csv; charset=UTF-8');
		//$this->response->headers(''Content-Dispositionattachment; filename=Customers_Export.csv');
		$filename = $this->table->export();
		$this->response->body("\xEF\xBB\xBF".file_get_contents($filename));
		//$this-> "\xEF\xBB\xBF"; // UTF-8 BOM
		$download_name = date('Y-m-d').'_'.$this->table->get_name().'.csv';
		$this->response->send_file(TRUE, $download_name);
		return;

//		$id = $this->request->param('id', FALSE);
//		$export_name = ($id) ? $id : uniqid();
//		$this->view->export_name = $export_name;
//		$this->view->progress = 0;
//		if ($id)
//		{
//			$this->table->add_GET_filters();
//
//			// Get temp file ready
//			$export_dir = Kohana::$cache_dir.DIRECTORY_SEPARATOR.'exports';
//			@mkdir($export_dir);
//			$filename = $export_dir.DIRECTORY_SEPARATOR.$export_name.'.csv';
//
//			// Send file if requested
//			if (isset($_GET['download']))
//			{
//				$download_name = date('Y-m-d').'_'.$this->table->get_name().'.csv';
//				$this->response->send_file($filename, $download_name);
//			}
//
//			// Set up file
//			$new = !file_exists($filename);
//			$file = fopen($filename, 'a');
//			if ($new)
//			{
//				// Add the column headers to the file.
//				$column_names = array_keys($this->table->get_columns());
//				$headers = Webdb_Text::titlecase($column_names);
//				fputcsv($file, $headers);
//			}
//
//			// Write data to the file
//			$pagination = $this->table->get_pagination();
//			$pagination->items_per_page = 500;
//			$rows = $this->table->get_rows();
//			foreach ($rows as $row)
//			{
//				$line = array(); // The line to write to CSV.
//				foreach ($this->table->get_columns() as $column) {
//					$edit = FALSE;
//					$form_field_name = '';
//					$field = View::factory('field')
//						->bind('column', $column)
//						->bind('row', $row)
//						->bind('edit', $edit)
//						->bind('form_field_name', $form_field_name)
//						->render();
//					$line[] = trim(strip_tags(trim($field)));
//				}
//				fputcsv($file, $line);
//			}
//
//			// Progress
//			$this->view->progress = round(($pagination->current_page / $pagination->total_pages) * 100);
//			$result = array(
//				'progress' => $this->view->progress,
//				'next_page' => $pagination->next_page,
//			);
//			exit(json_encode($result));
//
//		} // if ($id)

	} // public function action_export()

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
		if (!$this->table->can('insert'))
		{
			$this->template->content = '';
			$this->add_template_message('You do not have permission to import data into this table.');
			return;
		}

		// Set up the progress bar.
		$this->view->stages = array('choose_file', 'match_fields', 'preview', 'complete_import');

		// Stage 1: Uploading
		$url_params = array(
			'action' => 'import',
			'dbname' => $this->database->get_name(),
			'tablename' => $this->table->get_name(),
		);
		$this->view->form_action = Route::url('default', $url_params);
		$this->view->stage = $this->view->stages[0];
		try
		{
			$hash = $this->request->param('id', FALSE);
			$this->view->file = new WebDB_File_CSV($hash);
		} catch (Kohana_Exception $e)
		{
			$this->add_template_message($e->getMessage());
			return;
		}
		if ($this->view->file->loaded() && !$this->request->param('id'))
		{
			$url_params['id'] = $this->view->file->hash;
			$this->redirect(Route::url('default', $url_params, TRUE));
		}

		// Stage 2: Matching fields
		if ($this->view->file->loaded())
		{
			$this->view->stage = $this->view->stages[1];
			$params = array_merge($url_params, array('id'=>$hash));
			$this->view->form_action = Route::url('default', $params);
		}

		// Stage 3: Previewing
		if ($this->view->file->loaded() && isset($_POST['preview']))
		{
			$this->view->stage = $this->view->stages[2];
			$this->view->import_url = Route::url('default', $url_params);
			$this->view->columns = serialize($_POST['columns']);
			$this->view->errors = $this->view->file->match_fields($this->table, $_POST['columns']);
		}

		// Stage 4: Import
		if ($this->view->file->loaded() && isset($_POST['import']))
		{
			$this->view->stage = $this->view->stages[3];
			$result = $this->view->file->import_data($this->table, unserialize($_POST['columns']));
			$this->add_template_message('Import complete; '.$result.' rows imported.', 'info');
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
			$this->template->content = null;
			return;
		}

		// The permitted filter operators.
		$this->view->operators = $this->table->get_operators();
		foreach ($this->table->get_columns() as $col)
		{
			$this->view->columns[$col->get_name()] = WebDB_Text::titlecase($col->get_name());
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

}
