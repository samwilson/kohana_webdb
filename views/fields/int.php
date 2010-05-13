<?php $value = $row->{$column->get_name()} ?>

<?php if ($column->is_foreign_key() && $value): ?>

	<?php echo html::anchor('webdb/view/'.$value, $value) ?>

<?php elseif ($column->get_size() == 1): ?>

<?php if ($value==1) echo 'Yes'; elseif ($value===0) echo 'No'; else echo ''; ?>

<?php else: ?>

<span class="mono"><?php echo $row->{$column->get_name()} ?></span>

<?php endif ?>