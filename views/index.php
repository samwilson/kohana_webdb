

<table>
	<caption>
		<?php echo $pagination_links ?>
	</caption>
	<thead>
		<tr>
			<th>&nbsp;</th>
			<?php foreach ($columns as $column): ?>
			<th><?php echo text::titlecase($column['column_name']) ?></th>
			<?php endforeach ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($rows as $row): ?>
		<tr>
			<td><?php echo html::anchor("webdb/edit/$dbname/$tablename/".$row['id'], 'Edit') ?></td>
				<?php foreach ($columns as $column): ?>
			<td><?php echo text::titlecase($row[$column['column_name']]) ?></td>
				<?php endforeach ?>
		</tr>
		<?php endforeach ?>
	</tbody>
</table>

