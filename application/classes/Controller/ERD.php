<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_ERD extends Controller_Base {

	public function before()
	{
		parent::before();
		$this->database = new WebDB_Database;

		$this->selected_tables = array();
		foreach ($this->database->get_tables() as $table)
		{
			// If any tables are requested, only show them
			if (count($_GET) > 0)
			{
				if (isset($_GET[$table->get_name()]))
				{
					$this->selected_tables[] = $table->get_name();
				}
			}
			else // Otherwise, default to all linked tables
			{
				$referenced = count($table->get_referencing_tables()) > 0;
				$referencing = count($table->get_referenced_tables()) > 0;
				if ($referenced OR $referencing)
				{
					$this->selected_tables[] = $table->get_name();
				}
			}
		}
	}

	public function action_html()
	{
		$this->view = View::factory('erd/html');
		$this->view->database = $this->database;
		$this->view->selected_tables = $this->selected_tables;

		// Template
//		$template = View::factory('template');
//		$template->database = $this->database;
//		$template->tables = $this->database->get_tables(TRUE);
//		$template->table = '';
//		$template->controller = 'ERD';
//		$template->action = 'ERD';
		$this->template->content = $this->view->render();

		// Response
		//$this->response->body($template->render());
	}

	public function action_dot()
	{
		$this->template = View::factory('erd/dot');
		$this->template->database = $this->database;
		$this->template->selected_tables = $this->selected_tables;
		$this->response->headers('Content-Type', 'text/plain');
		$this->response->body($this->template->render());
		//$this
	}

	public function action_png()
	{
		$graph = Request::factory('/erd.dot')
			->execute()
			->body();
		$this->cache_dir = Kohana::$cache_dir.DIRECTORY_SEPARATOR.'webdb'.DIRECTORY_SEPARATOR.'erd';
		if ( ! is_dir($this->cache_dir))
		{
			mkdir($this->cache_dir, 0777, TRUE);
		}
		$dot_filename = $this->cache_dir.DIRECTORY_SEPARATOR.'erd.dot';
		$png_filename = $this->cache_dir.DIRECTORY_SEPARATOR.'erd.png';
		file_put_contents($dot_filename, $graph);
		$dot = Kohana::$config->load('webdb')->get('dot');
		$cmd = '"'.$dot.'"'.' -Tpng';
		$cmd .= ' -o'.escapeshellarg($png_filename); //output
		$cmd .= ' '.escapeshellarg($dot_filename); //input
		$cmd .= ' 2>&1';
		exec($cmd, $out, $error);
		if ($error != 0)
		{
			throw new HTTP_Exception_500('Unable to produce PNG. Command was: '.$cmd.' Output was: '.implode(PHP_EOL, $out));
		}
		else
		{
			$this->response->send_file($png_filename, 'erd.png', array('inline' => TRUE));
		}
	}

}
