<?php
$params = array('class'=>'datepicker', 'id'=>$form_field_name);
echo Form::input($form_field_name, $value, $params);
?>

<?php if ($column->get_comment()): ?>
<ul class="notes">
	<li><strong><?php echo $column->get_comment() ?></strong></li>
</ul>
<?php endif ?>
