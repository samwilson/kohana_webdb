<?php /*echo kohana::dump($table->get_rows()) ?></pre>
	<?php foreach ($table->get_rows() as $row): ?>
<pre><?php echo kohana::dump($row) ?></pre>
	<?php endforeach ?>
<?php exit */ ?>

<?php if ($table): ?>

<table>
	<caption>
			<?php echo $table->get_pagination()->render('webdb/pagination/truncated') ?>
	</caption>
	<thead>
		<tr>
				<?php if (in_array('id', array_keys($table->get_columns()))): ?>
			<th>&nbsp;</th>
				<?php endif ?>
				<?php foreach ($table->get_columns() as $column): ?>
			<th><?php echo Webdb_Text::titlecase($column->get_name()) ?></th>
				<?php endforeach ?>
		</tr>
	</thead>
	<tbody>
			<?php foreach ($table->get_rows() as $row): ?>
		<tr>
					<?php if (isset($row->id)): ?>
			<td>
							<?php
							if ($table->can_update() || $table->can_insert()):
								echo html::anchor('webdb/edit/'.$database->get_name().'/'.$table->get_name().'/'.$row->id, 'Edit');
							else:
								echo html::anchor('webdb/edit/'.$database->get_name().'/'.$table->get_name().'/'.$row->id, 'View');
							endif
							?>
			</td>
					<?php endif ?>
					<?php foreach ($table->get_columns() as $column): ?>
			<td class="<?php echo $column->get_type() ?>">
							<?php
							//echo kohana::dump($column);
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
							$cell_view->edit = FALSE;
							echo $cell_view->render();
							?>
			</td>
					<?php endforeach ?>
		</tr>
			<?php endforeach ?>
	</tbody>
</table>



<?php endif ?>