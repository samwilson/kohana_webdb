
<div class="progress">
	<div id="progress-bar"></div>
	<strong>Progress:</strong>
	<span id="progress-amount"><?php echo round($export['completed_count'] / $export['row_count']) ?></span>% completed.
	The displayed progress will update automatically, or you can <?php
	$url = 'export/'.$database->get_name().'/'.$table->get_name().'/'.$export['id'];
	echo HTML::anchor($url, 'bookmark this page');
	?> and return here later.
</div>

<script type="text/javascript">
	$(function() {
		$("#progress-bar").progressbar( { value:0 } );
		var url = "<?php echo URL::site('autocomplete/'.$database->get_name().'/exports?id='.$export['id']) ?>";
		setInterval(function() {
			$.getJSON(url, function(data) {
				data = data[0];
				var progress = Math.round((data.completed_count / data.row_count) * 100);
				$("#progress-amount").text(progress);
				$("#progress-bar").progressbar( { value:progress } );
				if (progress==100) {
					// Redirect
					clearInterval();
				}
			});
		}, 5000);
	});
</script>

