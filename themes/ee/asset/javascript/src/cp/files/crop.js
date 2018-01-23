/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('.form-standard form').on('submit', function (e) {
			// Only submit the inputs in the visible tab (by removing the other tab's inputs)
			$('.form-standard form input:not(:visible)').not('[type="hidden"]').remove();
		});
	});
})(jQuery);
