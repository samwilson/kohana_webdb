
<?php if ($table): ?>
<form action="" method="get">
	<table>
		<caption><p>Find records where&hellip;</p></caption>
        <?php for ($f=0; $f<count($filters); $f++): $filter = $filters[$f] ?>
        <tr>
            <td><?php if ($f>0) echo '&hellip;and' ?></td>
            <td><?php echo Form::select("filters[$f][column]", $columns, $filter['column']) ?></td>
            <td><?php echo Form::select("filters[$f][operator]", $operators, $filter['operator']) ?></td>
            <td colspan="2"><?php echo Form::input("filters[$f][value]", $filter['value']) ?></td>
        </tr>
        <?php endfor ?>
        <tr class="submit">
            <th colspan="3"></th>
            <th><input type="submit" value="Search" /></th>
            <th>
				<input type="submit" name="clear_filters" value="Clear Filters" <?php if(count($filters)<2)echo'disabled="disabled"'?>/>
			</th>
        </tr>
	</table>
</form>
<?php endif ?>


<?php echo View::factory('webdb/datatable', array('the_table' => $table))->render() ?>