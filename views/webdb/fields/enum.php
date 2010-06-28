<?php
/**
 * Edit
 */
if ($edit) {

	$options = $column->get_options();
	$selected = $row[$column->get_name()];
	$attributes = array('id' => $form_field_name);
	echo form::select($form_field_name, $options, $selected, $attributes);

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

