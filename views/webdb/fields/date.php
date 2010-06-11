<?php
if ($edit)
{
	/**
	 * Edit
	 */
	echo form::input(
	$column->get_name(),
	$row[$column->get_name()],
	array('class'=>'datepicker', 'id'=>$column->get_name().'-column')
	);

} else
{
	/**
	 * Don't edit
	 */
	$value = $row[$column->get_name()];
	if ($value): ?>
<span class="mono">
			<?php echo $value ?>
</span>
	<?php endif ?>


	<?php } ?>

