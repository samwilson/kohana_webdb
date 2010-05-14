<?php if ($column->is_editable()): ?>
	
	<?php echo form::input($column->get_name(), $row->{$column->get_name()}) ?>

<?php else: ?>
	
	<?php echo $row->{$column->get_name()} ?>

<?php endif ?>