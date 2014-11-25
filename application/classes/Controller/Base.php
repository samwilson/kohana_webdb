<?php

abstract class Controller_Base extends Controller_Template
{

	public function before()
	{
		parent::before();

		/*
		 * Set up views
		 */
		if (Kohana::find_file('views', $this->request->action()))
		{
			$this->view = View::factory($this->request->action());
			$this->template->content = $this->view;
		}
		$this->template->set_global('tables', array());
		$this->template->set_global('table', FALSE);
		$this->template->messages = array();
		$this->template->controller = $this->request->controller();
		$this->template->action = $this->request->action();
		$this->template->actions = array(
			'index'  => 'Browse &amp; Search',
			'edit'   => 'New',
			'import' => 'Import',
			'export' => 'Export',
		);

		/*
		 * Add flash messages to the template, then clear them from the session.
		*/
		foreach (Session::instance()->get('flash_messages', array()) as $msg)
		{
			$this->add_template_message($msg['message'], $msg['status']);
		}
		Session::instance()->set('flash_messages', array());

		$this->_query_string_session();

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
		$flash_messages = Session::instance()->get('flash_messages', array());
		$flash_messages[] = array(
			'status'=>$status,
			'message'=>$message
		);
		Session::instance()->set('flash_messages', $flash_messages);
	}

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
				if ((empty($val) AND isset($_SESSION['qs'][$key])) OR ( ! in_array($key, $to_save)))
				{
					unset($_SESSION['qs'][$key]);
					continue;
				}
				$session[$key] = $val;
			}
			$_SESSION['qs'] = $session;
		}

		// Load query string variables, unless they're already present.
		if (isset($_SESSION['qs']) AND count($_SESSION['qs'])>0)
		{
			$has_new = FALSE; // Whether there's anything in SESSION that's not in GET
			foreach ($_SESSION['qs'] as $key=>$val)
			{
				if ( ! isset($_GET[$key]) AND in_array($key, $to_save))
				{
					$_GET[$key] = $val;
					$has_new = TRUE;
				}
			}
			// Don't redirect for POST requests.
			if ($has_new AND $_SERVER['REQUEST_METHOD']=='GET')
			{
				$query = URL::query($_SESSION['qs']);
				$_SESSION['qs'] = array();
				$uri = $this->request->uri().$query;
				$this->redirect($uri);
			}
		}
	}

}
