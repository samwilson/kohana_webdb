$(function() {

	// Date and time masks and pickers
	$('input.time').mask('99:99');
	$('input.datetime').mask('9999-99-99 99:99');
	$('input.datepicker').mask('9999-99-99');
	$('input.datepicker').datepicker( {dateFormat: 'yy-mm-dd'} );

	// Set initial focus element
	$('#focus-me').focus();

	// Table menu display
	$(".tables ol").hide();
	$(".tables .selected").parents(".tables ol").addClass("open").show();
	$(".tables .selected").parents(".tables ol").prev().children(".ui-icon").removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-s");
	$(".tables .section-head").click(function() {
		if ($(this).next().hasClass("open")) {
			$(this).next().slideUp("fast").removeClass("open");
			$(this).children(".ui-icon").removeClass("ui-icon-triangle-1-s").addClass("ui-icon-triangle-1-e");
		} else {
			$(this).next().addClass("open").slideDown("fast");
			$(this).children(".ui-icon").removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-s");
		}
	});

	// Filters
	var $addFilter = $("<a>Add new filter</a>");
	$("table.filters tr.submit th:first").prepend($addFilter);
	$addFilter.click(function() {
		var filterCount =  $(this).parents("table").find("tr.filter").size();
		$lastrow = $(this).parents("table").find("tr.filter:last");
		$newrow = $lastrow.clone();
		$newrow.find("select, input").each(function() {
			var newName = $(this).attr("name").replace(/\[[0-9]+\]/, "["+filterCount+"]")
			$(this).attr("name", newName);
		});
		$newrow.find("td:first").html("&hellip;and");
		$lastrow.after($newrow);
	});

});
