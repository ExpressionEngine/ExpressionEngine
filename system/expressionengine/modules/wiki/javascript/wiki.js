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

	$('.remove_namespace').click(function(e) {
		var namespace_id	= $(this).attr('name').substr(17),
			row				= $(this).parent().parent();

		$.ajax({
			dataType:	'json',
			data:		'namespace_id='+namespace_id,
			url:		EE.BASE+'&C=addons_modules&M=show_module_cp&module=wiki&method=delete_namespace',
			success:	function(result) {
				if (result.response === 'success') {
					$.ee_notice(EE.lang.namespace_deleted, {type: 'success', open: true, close_on_click: true});
					$(row).fadeOut('slow', function() {
						$(this).remove();
					});
				} else {
					$.ee_notice(EE.lang.namespace_not_deleted, {type: 'error', open: true, close_on_click: true});
				}
			}
		});
		
		return false;

	});
});
