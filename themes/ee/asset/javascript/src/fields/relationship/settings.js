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

"use strict";

(function ($) {
	$(document).ready(function () {
		$('input:checkbox[data-any]').on('click', function(e) {
			// If we clicked on the "Any..." option, if it is now
			// checked, we need to uncheck all the other checkboxes
			if ($(e.target).val() == '--') {
				if (e.target.checked) {
					$(e.target).closest('label')
						.siblings('ul')
						.find('input:checkbox:checked')
						.click();
				}
			}
			// If we did not click on the "Any..." option and we checked
			// something, then we need to uncheck "Any..."
			else {
				if (e.target.checked) {
					if (e.target.checked) {
						$(e.target).closest('ul.nested-list')
							.find('input:checkbox:checked[value="--"]')
							.click();
					}
				}
			}
		});
	});
})(jQuery);
