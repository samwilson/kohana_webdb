<?php if ($the_table): ?>

<?php $rows = $the_table->get_rows() ?>

<table>
	<caption>
			<?php echo $the_table->get_pagination()->render('webdb/pagination/truncated') ?>
		<!--p>Showing records x&ndash;x of <?php echo $the_table->count_records() ?></p-->
	</caption>
	<thead>
		<tr>
				<?php if (in_array('id', array_keys($the_table->get_columns()))): ?>
			<th>&nbsp;</th>
				<?php endif ?>
				<?php foreach ($the_table->get_columns() as $column): ?>
			<th><?php echo Webdb_Text::titlecase($column->get_name()) ?></th>
				<?php endforeach ?>
		</tr>
	</thead>
	<tbody>
			<?php foreach ($rows as $row): ?>
		<tr>
					<?php if (isset($row['id'])): ?>
			<td>
							<?php
							if ($the_table->can_update() || $the_table->can_insert()):
								echo html::anchor('webdb/edit/'.$database->get_name().'/'.$the_table->get_name().'/'.$row['id'], 'Edit');
							else:
								echo html::anchor('webdb/edit/'.$database->get_name().'/'.$the_table->get_name().'/'.$row['id'], 'View');
							endif
							?>
			</td>
					<?php endif ?>
					<?php foreach ($the_table->get_columns() as $column): ?>
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
