/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('table').eeTableReorder({
		afterSort: function(row) {
			$.ajax({
				url: EE.forums.reorder_url,
				data: {'order': $('input[name="order[]"]').serialize() },
				type: 'POST',
				dataType: 'json',
				error: function(xhr, text, error) {
					// Let the user know something went wrong
					if ($('body > .banner').size() == 0) {
						$('body').prepend(EE.alert.reorder_ajax_fail);
					}
				}
			});
		}
	});

	$('.tbl-ctrls').sortable({
		axis: 'y',						// Only allow vertical dragging
		containment: 'parent',			// Contain to parent
		handle: 'th.reorder-col',		// Set drag handle
		items: 'table',					// Only allow these to be sortable
		sort: EE.sortable_sort_helper,	// Custom sort handler
		forcePlaceholderSize: true,		// Custom sort handler
		update : function (event, ui){
			$.ajax({
				url: EE.forums.reorder_url,
				data: {'order': $('input[name="cat_order[]"]').serialize() },
				type: 'POST',
				dataType: 'json',
				error: function(xhr, text, error) {
					// Let the user know something went wrong
					if ($('body > .banner').size() == 0) {
						$('body').prepend(EE.alert.reorder_ajax_fail);
					}
				}
			});
		}
	});

});

})(jQuery);
