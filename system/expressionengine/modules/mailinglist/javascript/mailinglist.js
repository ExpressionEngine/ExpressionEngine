(function($) {
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
