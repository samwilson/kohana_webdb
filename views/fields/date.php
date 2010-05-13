<?php $value = $row->{$column->get_name()};
if ($value): ?>
<span class="mono">
		<?php echo $value ?>
</span>
<?php endif ?>

