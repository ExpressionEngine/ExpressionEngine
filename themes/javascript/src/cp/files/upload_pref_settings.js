$(document).ready(function() {
	$('.remove_size').click(function(e) {
		var size_id	= $(this).attr('size_short_name_').substr(16),
			row				= $(this).parent().parent();
			
			alert(size_id);

		$.ajax({
			dataType:	'json',
			data:		'id='+size_id,
			url:		EE.BASE+'&C=content_files&M=delete_dimension',
			success:	function(result) {
				if (result.response === 'success') {
					$.ee_notice(EE.lang.size_deleted, {type: 'success', open: true, close_on_click: true});
					$(row).fadeOut('slow', function() {
						$(this).remove();
					});
				} else {
					$.ee_notice(EE.lang.size_not_deleted, {type: 'error', open: true, close_on_click: true});
				}
			}
		});
		
		return false;

	});
});

