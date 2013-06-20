
<?php if ($column->is_foreign_key() && $value): ?>
	<?php
	$referenced_table = $column->get_referenced_table();
	$url = "edit/".$database->get_name().'/'.$referenced_table->get_name().'/'.$value;
	echo HTML::anchor($url, $row[$column->get_name().'_webdb_title']); //$referenced_table->get_title($value));
	?>

<?php elseif ($column->get_size() == 1): ?>
	<?php if ($value==='1') echo 'Yes'; elseif ($value==='0') echo 'No'; else echo ''; ?>

<?php else: ?>
	<?php echo $value ?>

<?php endif ?>
