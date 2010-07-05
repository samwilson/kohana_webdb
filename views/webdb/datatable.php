<?php if ($the_table): ?>

<?php $rows = $the_table->get_rows() ?>

<table>
	<caption>
			<p>
				Found <?php echo number_format($the_table->count_records()) ?>
				record<?php if ($the_table->count_records()!=1) echo 's' ?>
			</p>
			<?php echo $the_table->get_pagination()->render('pagination/floating') ?>
	</caption>
	<thead>
		<tr>

			<?php if (in_array('id', array_keys($the_table->get_columns()))): ?>
			<th>&nbsp;</th>
			<?php endif ?>

			<?php foreach ($the_table->get_columns() as $column)
			{
				$title = Webdb_Text::titlecase($column->get_name());
				$orderdir = $the_table->orderdir;
				$class = '';
				if ($the_table->orderby==$column->get_name())
				{
					$title .= "&nbsp;<img src='".URL::site("webdb/resources/img/sort_$orderdir.png")."' alt='Sort-direction icon' />";
					$orderdir = ($orderdir=='desc') ? 'asc' : 'desc';
					$class = 'sorted';
				}
				$url = URL::query(array('orderby'=>$column->get_name(), 'orderdir'=>$orderdir));
				echo "<th class='$class'>".HTML::anchor(Request::instance()->uri.$url, $title)."</th>";
			} ?>

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
