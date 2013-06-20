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

			<?php if ($the_table->get_pk_column()): ?>
			<th>&nbsp;</th>
			<?php endif ?>

			<?php foreach ($the_table->get_columns() as $column)
			{
				$title = WebDB_Text::titlecase($column->get_name());
				$orderdir = $the_table->orderdir;
				$class = '';
				if ($the_table->orderby==$column->get_name())
				{
					$title .= "&nbsp;<img src='".URL::site("resources/img/sort_$orderdir.png")."' alt='Sort-direction icon' />";
					$orderdir = ($orderdir=='desc') ? 'asc' : 'desc';
					$class = 'sorted';
				}
				$url = URL::query(array('orderby'=>$column->get_name(), 'orderdir'=>$orderdir));
				echo "<th class='$class'>".HTML::anchor(Request::current()->uri().$url, $title)."</th>";
			} ?>

		</tr>
	</thead>
	<tbody>
			<?php $new_row_ident = 0;
			foreach ($rows as $row):
			$new_row_ident++; ?>
		<tr>
			
			<?php if ($the_table->get_pk_column()): ?>
			<td>
				<?php
				$pk_name = $the_table->get_pk_column()->get_name();
				$label = ($the_table->can('update')) ? 'Edit' : 'View';
				$url = 'edit/'.$database->get_name().'/'.$the_table->get_name().'/'.$row[$pk_name];
				echo html::anchor($url, $label);
				?>
			</td>
			<?php endif // if ($the_table->get_pk_column()) ?>
			
					<?php foreach ($the_table->get_columns() as $column): ?>
			<td class="<?php echo $column->get_type(); if ($column->is_boolean()) echo ' boolean'; ?>">
				<?php $edit = FALSE;
				$form_field_name = '';
				echo View::factory('field')
					->bind('column', $column)
					->bind('row', $row)
					->bind('edit', $edit)
					->bind('form_field_name', $form_field_name)
					->render() ?>
			</td>
					<?php endforeach ?>
		</tr>
			<?php endforeach ?>
	</tbody>
</table>



<?php endif ?>
