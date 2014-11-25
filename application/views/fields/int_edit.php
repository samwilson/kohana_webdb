<?php

/**
 * ID column
 */
if ($column->is_primary_key())
{
	$params = array('readonly'=>TRUE, 'id'=>$form_field_name, 'size'=>$column->get_size());
	echo Form::input($form_field_name, $value, $params);

}
/**
 * Booleans
 */
elseif ($column->is_boolean())
{
	if ($column->is_required())
	{
		echo Form::checkbox($form_field_name, NULL, $value==1, array('id'=>$form_field_name));
	}
	else
	{
		echo Form::select($form_field_name, array(''=>'', '1'=>'Yes', '0'=>'No'));
	}
}
/**
 * Foreign keys
 */
elseif($column->is_foreign_key())
{
	$referenced_table = $column->get_referenced_table();

	$foreign_count = $referenced_table->count_records();
	if ($foreign_count < $table->get_pagination(FALSE)->items_per_page):
		$options = array();
		if ( ! $column->is_required()) $options[] = '';
		foreach ($referenced_table->get_rows(TRUE, FALSE) as $foreign_row) {
			$options[$foreign_row['id']] = $foreign_row['webdb_title'];
		}
		echo Form::select($form_field_name, $options, $value);
	else:
	?>

<script type="text/javascript">
	<?php $fk_actual_value_field = str_replace('[','\\[',str_replace(']','\\]',$form_field_name)) ?>
	<?php $fk_field_name = str_replace('[','_',str_replace(']','_',$form_field_name)).'_label' ?>
	$(function() {
		var fk_field_name = '<?php echo $fk_field_name ?>';
		$("[name='"+fk_field_name+"']").autocomplete({
			source: "<?php echo URL::site('autocomplete/'.$referenced_table->get_name()) ?>",
			select: function(event, ui) {
				var fk_actual_value_field = '<?php echo $fk_actual_value_field ?>';
				$("[name='"+fk_actual_value_field+"']").val(ui.item.id);
				return true;
			},
			change: function(event, ui) {
				if ($(this).val().length==0) {
					var fk_actual_value_field = '<?php echo $fk_actual_value_field ?>';
					$("[name='"+fk_actual_value_field+"']").val('');
					return true;
				}
			}
		});
	});
</script>

<?php $form_field_value = $value;
if ($value) {
	$form_field_value = (isset($row[$column->get_name().'_webdb_title']))
	? $row[$column->get_name().'_webdb_title']
	: $referenced_table->get_title($value);
} ?>
<input type="text" class="foreign-key-actual-value" readonly
	   name="<?php echo $form_field_name ?>"
	   size="<?php echo (empty($value)) ? 1 : strlen($value) ?>"
	   value="<?php echo $value ?>" />
<input type="text" class="foreign-key"
	   name="<?php echo $fk_field_name ?>"
	   size="<?php echo min(35, strlen($form_field_value)) ?>"
	   value="<?php echo $form_field_value ?>" />

<?php endif // if ($foreign_count < $table->get_pagination(FALSE)->items_per_page) ?>

<ul class="notes">
	<li>
		This is a cross-reference to
		<?php 
		$url = "index/".$referenced_table->get_name();
		$title = WebDB_Text::titlecase($referenced_table->get_name());
		echo html::anchor($url, $title) ?>.
	</li>
		<?php if(isset($row[$column->get_name().'_webdb_title'])): // Won't work for default values in new records. ?>
	<li>
		<?php
		$url = "edit/".$referenced_table->get_name().'/'.$value;
		$title = 'View '.$row[$column->get_name().'_webdb_title']; //$referenced_table->get_title($value);
		echo HTML::anchor($url, $title) ?>
		(<?php echo WebDB_Text::titlecase($referenced_table->get_name()) ?>
		record #<?php echo $value ?>).
	</li>
		<?php endif ?>
</ul>


<?php
}
/**
 * Everything else
 */
else
{
	$attrs = array('id'=>$form_field_name, 'size'=>min(35, $column->get_size()));
	echo Form::input($form_field_name, $value, $attrs);

} // end ifs choosing type of input.
?>



<?php if ($column->get_comment()): ?>
<ul class="notes">
	<li><strong><?php echo $column->get_comment() ?></strong></li>
</ul>
<?php endif ?>


