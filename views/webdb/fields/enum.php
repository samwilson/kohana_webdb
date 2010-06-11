<?php
/**
 * Edit
 */
if ($edit):
	
	$name = $column->get_name();
	$options = $column->get_options();
	$selected = $row[$column->get_name()];
	$attributes = array('id' => $name.'-column');
	echo form::select($name, $options, $selected, $attributes);

/**
 * Don't edit
 */ else:

	echo $row[$column->get_name()];

endif ?>