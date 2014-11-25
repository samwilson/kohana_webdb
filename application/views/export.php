
<div class="progress">
	<div id="progress-bar"></div>
	<strong>Export Progress:</strong>
	<span id="progress-amount"><?php echo $progress ?></span>% completed.
	<a id="download-link" style="display:none">Download now.</a>
</div>

<script type="text/javascript">
	$(function() {
		$("#progress-bar").progressbar( { value:0 } );
		$("#progress-bar .ui-progressbar-value").animate({width:"1%"}, "slow");
		exportjob(1);
	});
	function exportjob(page) {
		var url = "<?php echo URL::site('export/'.$database->get_name().'/'.$table->get_name().'/'.$export_name) ?>";
		$.getJSON(url+"?page="+page, function(data) {
			var progress = data.progress;
			$("#progress-amount").text(progress);
			$("#progress-bar .ui-progressbar-value").animate({width:progress+"%"}, "slow");
			if (progress==100) {
				var download_url = url+"?download=1";
				$("#download-link")
					.attr('href', download_url)
					.show('slow');
				window.location = download_url;
			} else {
				exportjob(data.next_page);
			}
		});
	}
</script>

