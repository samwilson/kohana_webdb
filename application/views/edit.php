<?php if (!isset($row['id']) && !$table->can('insert')) return ?>

<?php $num_cols = 3 ?>

<script type="text/javascript">
	$(function() {
		$("input,textarea,select").focus(function(){
			var this_id = $(this).attr('id');
			$(this).parents('tr').find("label[for='"+this_id+"']").parents('th').attr('class', 'focused');
			$(this).parents('td').attr('class', 'focused');
		});
		$("input,textarea,select").blur(function(){
			var this_id = $(this).attr('id');
			$(this).parents('tr').find("label[for='"+this_id+"']").parents('th').attr('class', '');
			$(this).parents('td').attr('class', '');
		});
	});
</script>


<?php echo Form::open(Request::current()->uri()) ?>
<table class="edit-form">

	<?php $columns = array_values($table->get_columns()) //; exit(kohana::debug(ceil(count($columns)))) ?>
	<?php for ($row_num=0; $row_num<ceil(count($columns)); $row_num++ ): ?>

	<tr>
			<?php
			for ($col_num=0; $col_num<$num_cols; $col_num++):
				if (!isset($columns[$row_num * $num_cols + $col_num])) continue;
				$column = $columns[$row_num * $num_cols + $col_num];
				$form_field_name = (isset($row['id']) && is_numeric($row['id']))
					? 'data['.$row['id'].']['.$column->get_name().']'
					: 'data[new]['.$column->get_name().']';
				?>
		<th>
			<label for="<?php echo $form_field_name ?>"
				   title="Column type: <?php echo $column->get_type() ?>">
							   <?php echo Webdb_Text::titlecase($column->get_name()) ?>
			</label>
		</th>
		<td>
			<?php $edit = $column->can('update') || $column->can('insert');
			echo View::factory('field')
				->bind('column', $column)
				->bind('row', $row)
				->bind('edit', $edit)
				->bind('form_field_name', $form_field_name)
				->render() ?>
		</td>
			<?php endfor // columns ?>
	</tr>

	<?php endfor // rows ?>

	<?php if ($table->can('update') || $table->can('insert')): ?>
	<tfoot>
		<tr>
			<td colspan="<?php echo $num_cols * 2 ?>">
					<?php echo Form::submit('save', 'Save') ?>
			</td>
		</tr>
	</tfoot>
	<?php endif ?>
</table>


<?php echo Form::close() ?>



<?php $related_tables = $table->get_referencing_tables();
if (isset($row['id']) && count($related_tables) > 0): ?>
<script type="text/javascript">
	$().ready(function(){
		$('.related-tables h3').click(function() {
			$(this).next().toggle('slow');
			return false;
		}).next().hide();
	});
</script>

<div class="related-tables">
	<h2>Related Records:</h2>
	<ol>
			<?php foreach ($related_tables as $foreign):
				$foreign_column = $foreign['column'];
				$foreign_table = $foreign['table'];
				$foreign_table->reset_filters();
				$foreign_table->add_filter($foreign_column, '=', $table->get_title($row['id']));
				$num_foreign_records = $foreign_table->count_records();
				$class = ($num_foreign_records > 0) ? '' : 'no-records';
				?>
		<li>
			<h3 title="Show or hide these related records" class="anchor <?php echo $class ?>">
				<?php echo Webdb_Text::titlecase($foreign_table->get_name()) ?>
				<span class="smaller">(as &lsquo;<?php echo Webdb_Text::titlecase($foreign_column) ?>&rsquo;).</span>
				<?php echo $num_foreign_records ?> record<?php echo ($num_foreign_records!=1) ? 's' : '' ?>.
			</h3>
			<div>
				<p class="new-record">
				<?php $url = 'edit/'.$database->get_name().'/'.$foreign_table->get_name().'?'.$foreign_column.'='.$row['id'];
				echo HTML::anchor($url, 'Add a new record here.') ?>
				</p>
				<?php echo View::factory('datatable', array('the_table' => $foreign_table))->render() ?>
			</div>
		</li>
			<?php endforeach ?>
	</ol>
</div>

<?php endif ?>