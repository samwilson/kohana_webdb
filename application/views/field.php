<?php

$editable = $edit ? 'edit' : 'view';
$field_view_name = $column->get_type().'_'.$editable;

$view_file = Kohana::find_file('views/fields', $field_view_name);

if ($view_file)
{
	$field_view = View::factory('fields/'.$field_view_name);
} else
{
	$field_view = View::factory('fields/varchar_'.$editable);
}

$field_view->column = $column;
$field_view->row = $row;
$field_view->form_field_name = $form_field_name;
$field_view->value = $row[$column->get_name()];

echo $field_view->render();
