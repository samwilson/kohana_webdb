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
			<?php $new_row_ident = 0;
			foreach ($rows as $row):
			$new_row_ident++; ?>
		<tr>
					<?php if (isset($row['id'])): ?>
			<td>
					<?php
					if ($the_table->can('update') || $the_table->can('insert')):
						echo html::anchor('webdb/edit/'.$database->get_name().'/'.$the_table->get_name().'/'.$row['id'], 'Edit');
					else:
						echo html::anchor('webdb/edit/'.$database->get_name().'/'.$the_table->get_name().'/'.$row['id'], 'View');
					endif
					?>
			</td>
					<?php endif ?>
					<?php foreach ($the_table->get_columns() as $column): ?>
			<td class="<?php echo $column->get_type() ?>">
				<?php $edit = FALSE;
				$new_row_ident_label = 'new-'.$new_row_ident;
				echo View::factory('webdb/field')
					->bind('column', $column)
					->bind('row', $row)
					->bind('edit', $edit)
					->bind('new_row_ident', $new_row_ident_label)
					->render() ?>
			</td>
					<?php endforeach ?>
		</tr>
			<?php endforeach ?>
	</tbody>
</table>



<?php endif ?>
