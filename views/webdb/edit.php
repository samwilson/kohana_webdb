<?php if (!isset($row->id) && !$table->can_insert()) return ?>


<?php echo form::open() ?>

<?php $num_cols = 3 ?>

<table>

	<?php foreach ($table->get_columns() as $column): ?>

	<tr>
		<th>
			<label for="<?php echo $column->get_name() ?>">
					<?php echo Webdb_Text::titlecase($column->get_name()) ?>:
			</label>
		</th>
		<td>
				<?php
				$view_file = kohana::find_file('views/webdb/fields', $column->get_type());
				if ($view_file)
				{
					$cell_view = View::factory('webdb/fields/'.$column->get_type());
				} else
				{
					$cell_view = View::factory('webdb/fields/varchar');
				}
				$cell_view->column = $column;
				$cell_view->row = $row;
				$cell_view->edit = $column->can_update() || $column->can_insert();
				echo $cell_view->render();
				?>
		</td>
	</tr>

	<?php endforeach ?>

	<?php if ($table->can_update() || $table->can_insert()): ?>
	<tfoot>
		<tr>
			<td colspan="2">
					<?php echo form::submit('save', 'Save') ?>
			</td>
		</tr>
	</tfoot>
	<?php endif ?>
</table>


<?php echo form::close() ?>


<ol class="related-tables">
	<?php foreach ($table->get_referencing_tables() as $foreign_column => $foreign_table): ?>
	<li>
		<h3><?php echo Webdb_Text::titlecase($foreign_table->get_name()) ?></h3>
			<?php $foreign_table->where = array($foreign_column, '=', $row->id) ?>
			<?php echo View::factory('webdb/index', array('table'=>$foreign_table)) ?>
	</li>
	<?php endforeach ?>
</ol>

