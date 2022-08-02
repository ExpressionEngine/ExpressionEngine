/*!
* This source file is part of the open source project
* ExpressionEngine (https://expressionengine.com)
*
* @link      https://expressionengine.com/
* @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
* @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
*/

"use strict";

(function ($) {
	$(document).ready(function () {

		$('body').on('change','.selectable_buttons .button input[type="checkbox"]', function (e) {

			if ( !($(this).parents('.button-group').hasClass('multiple')) ) {
				var elParent = $(this).parents('.selectable_buttons');
				$(elParent).find('.button input[type="checkbox"]').not(this).prop('checked', false);
			}

			$(this).parents('.button-group').find('.button input[type="checkbox"]').each(function () {
				if ($(this).prop('checked')) {
					$(this).parent().addClass('active');
				} else {
					$(this).parent().removeClass('active')
				}
			});
		});
	});
})(jQuery);