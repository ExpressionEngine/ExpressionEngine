/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

"use strict";

(function ($) {
	$(document).ready(function () {

		// Toggle widget on and off
		$('.widget-icon--on, .widget-icon--off').on('click', function(e) {
			var input = $(this).find('input');
			if ($(this).hasClass('widget-icon--on'))
			{
				input.val('n');
			}
			else
			{
				input.val('y');
			}

			$(this).toggleClass('widget-icon--on widget-icon--off');

			e.preventDefault();
		});

		// Make the widgets fields sortable
		$('.dashboard').sortable({
			containment: false,
			handle: '.widget-icon--reorder', // Set drag handle to the top box
			items: '.widget',			// Only allow these to be sortable
			sort: EE.sortable_sort_helper,	// Custom sort handler
			cancel: '.no-drag',
			start: function (event, ui) {
				//console.log('drag started');
			},
			stop: function (event, ui) {
				//console.log('drag stopped');
			}
		});

		//saving the form
		$('a[rel=save_layout]').on('click', function(e) {
			e.preventDefault();
			$('#save-dashboard-layout-form').submit();
		});
	});
})(jQuery);
