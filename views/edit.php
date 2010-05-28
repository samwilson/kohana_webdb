<table>

<?php foreach ($columns as $column): ?>

<tr>
	<th><?php echo text::titlecase($column->get_name()) ?></th>
	<td>
			<?php
			echo kohana::dump($column);
			/*
			$view_file = kohana::find_file('views/fields', $column->get_type());
			if ($view_file)
			{
				$cell_view = View::factory('fields/'.$column->get_type());
			} else
			{
				$cell_view = View::factory('fields/varchar');
			}
			$cell_view->column = $column;
			$cell_view->row = $row;
			echo $cell_view->render();
			 * 
			 */
			?>
	</td>
</tr>

<?php endforeach ?>
</table>