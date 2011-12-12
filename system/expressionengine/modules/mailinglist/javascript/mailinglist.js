(function($) {
	
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
	
	// Keyword filter
	var indicator = $('.searchIndicator');

	$('.mainTable')
	.table('add_filter', $('#filterMenu').find('form'))
	.bind('tableload', function() {
		indicator.css('visibility', '');
	})
	.bind('tableupdate', function() {
		indicator.css('visibility', 'hidden');
	});
	
})(jQuery);