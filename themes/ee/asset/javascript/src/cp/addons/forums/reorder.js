/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('tbody', 'table').sortable({
		connectWith: 'tbody',
		axis: 'y',						// Only allow vertical dragging
		handle: 'td.reorder-col',		// Set drag handle
		cancel: 'td.sort-cancel',		// Do not allow sort on this handle
		items: 'tr',					// Only allow these to be sortable
		sort: EE.sortable_sort_helper,	// Custom sort handler
		forcePlaceholderSize: true,		// Custom sort handler
		helper: function(event, row)	// Fix issue where cell widths collapse on drag
		{
			var $originals = row.children();
			var $helper = row.clone();

			// Make sure radio buttons retain their state after sort,
			// explanation:
			// Upon finishing the sort, the new row will be put down
			// before the helper is destroyed, so for a brief moment
			// in time, there are multiple sets of radios with the
			// same name, and given the nature of radio buttons, only
			// one can be selected within the same name group, and the
			// helper wins; so, we'll just assign a random name to each
			$helper.find('input[type=radio]:enabled').each(function() {
				$(this).attr('name', Math.random() * 20);
			});

			$helper.children().each(function(index)
			{
				// Set helper cell sizes to match the original sizes
				$(this).width($originals.eq(index).width())
			});

			return $helper;
		},
		stop: function(row) {
			$.ajax({
				url: EE.forums.reorder_url,
				data: {'order': $('input[name="order[]"]').serialize() },
				type: 'POST',
				dataType: 'json',
				error: function(xhr, text, error) {
					// Let the user know something went wrong
					if ($('body > .banner').length == 0) {
						$('body').prepend(EE.alert.reorder_ajax_fail);
					}
				}
			});
		}
	});

	$('.tbl-ctrls').sortable({
		axis: 'y',						// Only allow vertical dragging
		handle: 'th.reorder-col',		// Set drag handle
		items: '.tbl-wrap',					// Only allow these to be sortable
		sort: EE.sortable_sort_helper,	// Custom sort handler
		forcePlaceholderSize: true,		// Custom sort handler
		update : function (event, ui){
			$.ajax({
				url: EE.forums.reorder_url,
				data: {'order': $('input[name="order[]"]').serialize() },
				type: 'POST',
				dataType: 'json',
				error: function(xhr, text, error) {
					// Let the user know something went wrong
					if ($('body > .banner').length == 0) {
						$('body').prepend(EE.alert.reorder_ajax_fail);
					}
				}
			});
		}
	});

});

})(jQuery);
