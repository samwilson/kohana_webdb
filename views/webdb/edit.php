
<?php echo form::open() ?>


<table>

	<?php foreach ($columns as $column): ?>

	<tr>
		<th>
			<label for="<?php echo $column->get_name() ?>">
					<?php echo Webdb_Text::titlecase($column->get_name()) ?>:
			</label>
		</th>
		<td><?php //echo kohana::debug($row) ?>
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
				$cell_view->edit = $column->is_editable();
				echo $cell_view->render();
				?>
		</td>
	</tr>

	<?php endforeach ?>

	<?php if ($column->is_editable()): ?>
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


