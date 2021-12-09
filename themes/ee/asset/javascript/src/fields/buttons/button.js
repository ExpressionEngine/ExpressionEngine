/*!
* This source file is part of the open source project
* ExpressionEngine (https://expressionengine.com)
*
* @link      https://expressionengine.com/
* @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
* @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
*/

"use strict";

(function ($) {
	$(document).ready(function () {

		$('body').on('change','.button-group .button input[type="checkbox"]', function (e) {

			if ( !($(this).parents('.button-group').hasClass('multiple')) ) {
				$('.button-group .button input[type="checkbox"]').not(this).prop('checked', false);
			}

			$('.button-group .button input[type="checkbox"]').each(function () {
				if ($(this).prop('checked')) {
					$(this).parent().addClass('active');
				} else {
					$(this).parent().removeClass('active')
				}
			});
		});
	});
})(jQuery);