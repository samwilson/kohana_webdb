<?php
if ($edit)
{
	/**
	 * Edit
	 */
	echo form::input(
		$form_field_name,
		$row[$column->get_name()],
		array('class'=>'datepicker', 'id'=>$form_field_name)
	);

	if ($column->get_comment()) {
	echo '<ul class="notes">'
		.'<li><strong>'.$column->get_comment().'</strong></li>'
		.'</ul>';
	}

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

