$(document).ready(function() {
	$(".mainTable").tablesorter({
		headers: {2: {sorter: false}},
		widgets: ["zebra"]
	});
	$(".toggle_all").toggle(
		function(){
			$("input.toggle").each(function() {
				this.checked = true;
			});
		}, function (){
			var checked_status = this.checked;
			$("input.toggle").each(function() {
				this.checked = false;
			});
		}
	);
});
