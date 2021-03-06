
<?php if ($table): ?>
<form action="<?php echo URL::site(Request::current()->uri()) ?>" method="get">
	<table class="filters">
		<caption><p>Find records where&hellip;</p></caption>
		<?php for ($f=0; $f<count($filters); $f++): $filter = $filters[$f] ?>
		<tr class="filter" data-filter-num="<?php echo $f ?>">
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
				<?php if(count($filters)>1) echo HTML::anchor(Request::current()->uri().URL::query(array('filters' => '')), 'Clear Filters') ?>
			</th>
		</tr>
	</table>
</form>
<?php endif ?>


<?php echo View::factory('datatable', array('the_table' => $table))->render() ?>