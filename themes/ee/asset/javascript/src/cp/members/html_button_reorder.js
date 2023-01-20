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

	$('table').eeTableReorder({
		appendTo: 'table',
		afterSort: function(row) {
			$.ajax({
				url: EE.html_buttons.reorder_url,
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
