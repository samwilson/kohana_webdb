<?php /**
 * Edit
 */ if ($edit): ?>

	<?php if ($column->get_size() > 0): ?>
		<?php if($column->get_size() < 150): ?>
			<?php echo form::input($column->get_name(), $row->{$column->get_name()}, array('size'=>$column->get_size())) ?>

		<?php else: ?>
<textarea name="<?php echo $column->get_name() ?>" cols="80" rows="4"><?php echo $row->{$column->get_name()} ?></textarea>
		<?php endif ?>

<ul class="notes">
	<li>Maximum length: <?php echo $column->get_size() ?> characters.</li>
</ul>
	<?php else: ?>
		<?php echo form::input($column->get_name(), $row->{$column->get_name()}) ?>
	<?php endif ?>


<?php /**
 * Don't edit
 */ else: ?>

	<?php echo $row->{$column->get_name()} ?>

<?php endif ?>