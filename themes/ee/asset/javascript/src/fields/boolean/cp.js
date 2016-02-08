/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.2.0
 * @filesource
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
