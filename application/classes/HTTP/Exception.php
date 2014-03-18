<?php

class HTTP_Exception extends Kohana_HTTP_Exception {

	/**
	 * Generate a Response for all Exceptions without a more specific override
	 *
	 * @return Response
	 */
	public function get_response()
	{
		// Lets log the Exception, Just in case it's important!
		Kohana_Exception::log($this);

		// View
		$view = View::factory('error');
		$view->exception = $this;
		$view->previous = $this->getPrevious();

		// Page
		$page = View::factory('template');
		$page->controller = 'error';
		$page->action = 'error';
		$page->content = $view->render();

		// Response
		$response = Response::factory()
				->status($this->getCode())
				->body($page->render());

		return $response;
	}

}
