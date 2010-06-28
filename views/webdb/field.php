<?php
$view_file = kohana::find_file('views/webdb/fields', $column->get_type());
if ($view_file)
{
	$field_view = View::factory('webdb/fields/'.$column->get_type());
} else
{
	$field_view = View::factory('webdb/fields/varchar');
}
$field_view->column = $column;
$field_view->row = $row;
$field_view->edit = $edit;
$field_view->form_field_name = (isset($row['id']) && is_numeric($row['id']))
		? 'data['.$row['id'].']['.$column->get_name().']'
		: 'data['.$new_row_ident.']['.$column->get_name().']';
//$cell_view->new_row_ident = $new_row_ident;
echo $field_view->render();
