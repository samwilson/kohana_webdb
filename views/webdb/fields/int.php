<?php $colname = $column->get_name();
$value = $row->{$colname}; ?>

<?php /**
 * Edit
 */ if ($edit): ?>

	<?php if ($colname == 'id'): ?>
		<?php echo form::input('id', $value, array('readonly'=>TRUE)) ?>

	<?php else: ?>
		<?php echo form::input($colname, $value) ?>
	<?php endif ?>



<?php /**
 * Don't edit
 */ else: ?>

	<?php if ($column->is_foreign_key() && $value): ?>
		<?php
		$referenced_table = $column->get_referenced_table();
		$url = "webdb/edit/".$database->get_name().'/'.$referenced_table->get_name().'/'.$value;
		echo html::anchor($url, $referenced_table->get_title($value));
		?>

	<?php elseif ($column->get_size() == 1): ?>
		<?php if ($value===1) echo 'Yes'; elseif ($value===0) echo 'No'; else echo ''; ?>

	<?php else: ?>
		<?php echo $value ?>

	<?php endif ?>

<?php endif ?>

