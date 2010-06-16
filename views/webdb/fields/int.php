<?php
$colname = $column->get_name();
$value = $row[$colname];
$column_id = $colname.'-column';

/**
 * Edit
 */
if ($edit):


	/**
	 * ID column
	 */
	if ($colname == 'id'):
		echo form::input('id', $value, array('readonly'=>TRUE, 'id'=>$column_id));

	/**
	 * Booleans
	 */
	elseif ($column->get_size() == 1):
		echo form::checkbox($colname, $value, $value==1, array('id'=>$column_id));

	/**
	 * Foreign keys
	 */
	elseif($column->is_foreign_key()):
		$referenced_table = $column->get_referenced_table();
		?>

<script type="text/javascript">
	$(function() {
		$("[name='<?php echo $colname ?>_label']").autocomplete({
			source: "<?php echo url::site('webdb/autocomplete/'.$database->get_name().'/'.$referenced_table->get_name()) ?>",
			select: function(event, ui) {
				$(this).parent().children("[name='<?php echo $colname ?>']").val(ui.item.id);
				return false;
			}
		});
	});
</script>
<input type="text"
	   name="<?php echo $colname ?>_label"
	   id="<?php echo $column_id ?>"
	   value="<?php echo $referenced_table->get_title($value) ?>"
	   size="<?php echo min(35, $referenced_table->get_title_column()->get_size()) ?>" />
<input type="hidden" name="<?php echo $colname ?>" value="<?php echo $value ?>" />
<ul class="notes">
	<li>
		This is a cross-reference to
		<?php 
		$url = "webdb/index/".$database->get_name().'/'.$referenced_table->get_name();
		$title = Webdb_Text::titlecase($referenced_table->get_name());
		echo html::anchor($url, $title) ?>.
	</li>
		<?php if($value): ?>
	<li>
		View
		<?php
		$url = "webdb/edit/".$database->get_name().'/'.$referenced_table->get_name().'/'.$value;
		$title = $referenced_table->get_title($value);
		echo html::anchor($url, $title) ?>
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
		<?php echo form::input($colname, $value,  array('id'=>$column_id, 'size'=>min(35, $column->get_size()))) ?>

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
		$url = "webdb/edit/".$database->get_name().'/'.$referenced_table->get_name().'/'.$value;
		echo html::anchor($url, $referenced_table->get_title($value));
		?>

	<?php elseif ($column->get_size() == 1): ?>
		<?php if ($value==1) echo 'Yes'; elseif ($value==0) echo 'No'; else echo ''; ?>

	<?php else: ?>
		<?php echo $value ?>

	<?php endif ?>

<?php endif ?>

