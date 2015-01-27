<?php

$items = array();

$items[] = array(
	'url' => 'erd',
	'title' => 'ERD',
);

if (Auth::instance()->logged_in())
{
	$user = new WebDB_User(Auth::instance()->get_user());
	$items[] = array(
		'url' => Route::get('default')->uri(array('action' => 'edit', 'tablename' => 'users', 'id'=>$user->get_id())),
		'title' => 'Logged in as '.$user->get_username(),
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
