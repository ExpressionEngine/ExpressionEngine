/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('table').eeTableReorder({
		afterSort: function(row) {
			$.ajax({
				url: EE.quick_links.reorder_url,
				data: {'order': $('input[name="order[]"]').serialize() },
				type: 'POST',
				dataType: 'json',
				success: function(result) {
					$('table tbody').empty().append($(result.success).find('tr:has(td)'));
				},
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
