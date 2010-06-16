<?php
$value = $row[$column->get_name()];
/**
 * Edit
 */
if ($edit):
	$column_id = $column->get_name().'-column';
	?>

	<?php if ( $column->get_size() > 0 || $column->get_type()=='text' ): ?>
		<?php if($column->get_size() > 0 && $column->get_size() < 150):
			echo form::input(
			$column->get_name(),
			$value,
			array('size'=>min($column->get_size(),35), 'id'=>$column_id)
			)
			?>

		<?php else: ?>
<textarea name="<?php echo $column->get_name() ?>" cols="35" rows="4"
		  id="<?php echo $column_id?>"><?php echo $value ?></textarea>
				  <?php endif ?>

	<?php else: ?>
		<?php echo form::input($column->get_name(), $value, array('id'=>$column_id)) ?>
	<?php endif ?>

<ul class="notes">
	<?php if ($column->get_comment()): ?>
	<li><strong><?php echo $column->get_comment() ?></strong></li>
	<?php endif ?>
	<?php if ($column->get_size() > 0): ?>
	<li>Maximum length: <?php echo $column->get_size() ?> characters.</li>
	<?php endif ?>
</ul>



<?php /**
 * Don't edit
 */ else: ?>

	<?php echo $value ?>

<?php endif ?>