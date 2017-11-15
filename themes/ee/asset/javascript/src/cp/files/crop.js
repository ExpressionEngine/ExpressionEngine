/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('.form-standard form').on('submit', function (e) {
			$('.form-standard form input:not(:visible)').not('[type="hidden"]').attr('disabled', 'disabled');
		});
	});
})(jQuery);
