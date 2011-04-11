<?php
$view_file = kohana::find_file('views/fields', $column->get_type());
if ($view_file)
{
	$field_view = View::factory('fields/'.$column->get_type());
} else
{
	$field_view = View::factory('fields/varchar');
}
$field_view->column = $column;
$field_view->row = $row;
$field_view->edit = $edit;
$field_view->form_field_name = $form_field_name;
echo $field_view->render();
