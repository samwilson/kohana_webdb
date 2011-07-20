<?php
$value = $row[$column->get_name()];


/**
 * Edit
 */
if ($edit):


	/**
	 * ID column
	 */
	if ($column->get_name() == 'id'):
		echo form::input($form_field_name, $value, array('readonly'=>TRUE, 'id'=>$form_field_name, 'size'=>$column->get_size()));

	/**
	 * Booleans
	 */
	elseif ($column->get_size() == 1):
		echo form::checkbox($form_field_name, NULL, $value==1, array('id'=>$form_field_name));

	/**
	 * Foreign keys
	 */
	elseif($column->is_foreign_key()):
		$referenced_table = $column->get_referenced_table();
		?>

<script type="text/javascript">
	<?php $js_field_name = str_replace('[','\\[',str_replace(']','\\]',$form_field_name)) ?>
	<?php $fk_field_name = str_replace('[','_',str_replace(']','_',$form_field_name)).'_label' ?>
	$(function() {
		var field_to_autocomplete = '<?php echo $fk_field_name ?>';
		$("[name='"+field_to_autocomplete+"']").autocomplete({
			source: "<?php echo URL::site('autocomplete/'.$database->get_name().'/'.$referenced_table->get_name()) ?>",
			select: function(event, ui) {
				var field_to_autocomplete = '<?php echo $js_field_name ?>';
				$("[name='"+field_to_autocomplete+"']").val(ui.item.id);
				return true;
			}
		});
	});
</script>

<?php $form_field_value = ($value>0) ? $referenced_table->get_title($value) : '' ?>
<input type="text" class="foreign-key-actual-value" readonly
	   name="<?php echo $form_field_name ?>"
	   size="<?php echo (empty($value)) ? 1 : strlen($value) ?>"
	   value="<?php echo $value ?>" />
<input type="text" class="foreign-key"
	   name="<?php echo $fk_field_name ?>"
	   size="<?php echo min(35, strlen($form_field_value)) ?>"
	   value="<?php echo $form_field_value ?>" />
<ul class="notes">
	<li>
		This is a cross-reference to
		<?php 
		$url = "index/".$database->get_name().'/'.$referenced_table->get_name();
		$title = Webdb_Text::titlecase($referenced_table->get_name());
		echo html::anchor($url, $title) ?>.
	</li>
		<?php if($value): ?>
	<li>
		<?php
		$url = "edit/".$database->get_name().'/'.$referenced_table->get_name().'/'.$value;
		$title = 'View '.$referenced_table->get_title($value);
		echo HTML::anchor($url, $title) ?>
		(<?php echo Webdb_Text::titlecase($referenced_table->get_name()) ?>
		record #<?php echo $value ?>).
	</li>
		<?php endif ?>
</ul>


	<?php
	/**
	 * Everything else
	 */
	else: ?>
		<?php echo form::input($form_field_name, $value,  array('id'=>$form_field_name, 'size'=>min(35, $column->get_size()))) ?>

	<?php endif /* end ifs choosing type of input. */ ?>



	<?php
	if ($column->get_comment()) {
	echo '<ul class="notes">'
		.'<li><strong>'.$column->get_comment().'</strong></li>'
		.'</ul>';
	}
	?>



<?php /**
 * Don't edit
 */ else: ?>

	<?php if ($column->is_foreign_key() && $value): ?>
		<?php
		$referenced_table = $column->get_referenced_table();
		$url = "edit/".$database->get_name().'/'.$referenced_table->get_name().'/'.$value;
		echo HTML::anchor($url, $referenced_table->get_title($value));
		?>

	<?php elseif ($column->get_size() == 1): ?>
		<?php if ($value==1) echo 'Yes'; elseif ($value==0) echo 'No'; else echo ''; ?>

	<?php else: ?>
		<?php echo $value ?>

	<?php endif ?>

<?php endif ?>
