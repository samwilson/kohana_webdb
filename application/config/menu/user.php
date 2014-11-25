<?php

$items = array();

if (Auth::instance()->logged_in())
{
	$items[] = array(
		'title' => 'Logged in as '.Auth::instance()->get_user(),
	);
	$items[] = array(
		'url' => 'logout',
		'title' => 'Logout',
	);
} else
{
	$items[] = array(
		'url' => 'login?return_to='.urlencode(URL::base(Request::current())),
		'title' => 'Login',
	);
}

return array('items' => $items);
