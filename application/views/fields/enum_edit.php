<?php
$options = $column->get_options();
$attributes = array('id' => $form_field_name);
echo Form::select($form_field_name, $options, $value, $attributes);
?>

<?php if ($column->get_comment()): ?>
<ul class="notes">
	<li><strong><?php echo $column->get_comment() ?></strong></li>
</ul>
<?php endif ?>
