
<?php if ( $column->get_size() > 0 || $column->get_type()=='text' ): ?>

	<?php if($column->get_size() > 0 && $column->get_size() < 150): ?>

		<?php
		$params = array('size'=>min($column->get_size(),35), 'id'=>$form_field_name);
		echo Form::input($form_field_name, $value, $params);
		?>

	<?php else: ?>

		<textarea name="<?php echo $form_field_name ?>" cols="35" rows="4" id="<?php echo $form_field_name?>"><?php echo $value ?></textarea>

	<?php endif ?>

<?php else: ?>

	<?php echo Form::input($form_field_name, $value, array('id'=>$form_field_name)) ?>

<?php endif ?>

<ul class="notes">
	<?php if ($column->get_comment()): ?>
	<li><strong><?php echo $column->get_comment() ?></strong></li>
	<?php endif ?>
	<?php if ($column->get_size() > 0): ?>
	<li>Maximum length: <?php echo $column->get_size() ?> characters.</li>
	<?php endif ?>
</ul>


