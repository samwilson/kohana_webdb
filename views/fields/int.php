<pre><?php echo kohana::dump($column) ?></pre>

<?php $colname = $column->get_name();
$value = $row->{$colname} ?>

<?php if ($column->is_foreign_key() && $value): ?>

	<?php echo html::anchor('webdb/edit/'.$database->get_name().'/'.$table->get_name().'/'.$value, $value) ?>

<?php elseif ($column->get_size() == 1): ?>

	<?php if ($value==1) echo 'Yes'; elseif ($value===0) echo 'No'; else echo ''; ?>

<?php else: ?>

<span class="mono"><?php echo $row->{$column->get_name()} ?></span>

<?php endif ?>