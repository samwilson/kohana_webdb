<?php
/**
 * Edit
 */
if ($edit) {
	
	$name = $column->get_name();
	$options = $column->get_options();
	$selected = $row[$column->get_name()];
	$attributes = array('id' => $name.'-column');
	echo form::select($name, $options, $selected, $attributes);

	if ($column->get_comment()) {
		echo '<ul class="notes">'
			.'<li><strong>'.$column->get_comment().'</strong></li>'
			.'</ul>';
	}

/**
 * Don't edit
 */
} else {

	echo $row[$column->get_name()];

}

