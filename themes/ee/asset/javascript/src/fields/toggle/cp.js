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
		$('body').on('click', 'a.toggle-btn', function (e) {
			if ($(this).hasClass('disabled')) {
				return;
			}

			var input = $(this).find('input[type="hidden"]');

			if ($(this).hasClass('off')){
				$(this).removeClass('off');
				$(this).addClass('on');
				$(input).val(1);
			} else {
				$(this).removeClass('on');
				$(this).addClass('off');
				$(input).val(0);
			}

			e.preventDefault();
		});
	});
})(jQuery);
