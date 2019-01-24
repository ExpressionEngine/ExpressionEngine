/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {

	var replaceData = function(data) {
		$('.wrap .col-group:nth-child(2) .box').html(data.html);
		$.fuzzyFilter();

		$('input[name="search"]').closest('form').attr('action', data.url);

		if (jQuery().toggle_all) {
			$('table').toggle_all();
		}

		window.history.pushState(null, '', data.url);
	}

	// Submitting the search form
	$('input[name="search"]').closest('form').on('submit', function(event) {
		$.ajax({
			url: $(this).attr('action'),
			data: $(this).serialize(),
			type: 'POST',
			dataType: 'json',
			success: replaceData
		});

		event.preventDefault();
	});

	// Typing into the search form
	$('input[name="search"]').on('interact', _.debounce(function() {
		if (location.protocol === 'https:' &&
			navigator.userAgent.indexOf('Safari') > -1) {
			return;
		}
		$(this).closest('form').submit();
	}, 150));

	// Selecting a channel filter
	$('body').on('click', 'form > .filters .sub-menu a, .filters .filter-clear a, .paginate ul li a', function(event) {

		var search = $('input[name="search"]').serialize();

		$.ajax({
			url: $(this).attr('href') + '&' + search,
			type: 'GET',
			dataType: 'json',
			success: replaceData
		});

		event.preventDefault();
	});

});
