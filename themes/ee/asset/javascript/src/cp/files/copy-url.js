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
		$('.js-copy-url-button').on('click', function (e) {
			// copy asset link to clipboard
			var copyText = $(this).attr('href');

			document.addEventListener('copy', function(e) {
				e.clipboardData.setData('text/plain', copyText);
				e.preventDefault();
			}, true);

			document.execCommand('copy');

			// show notification
			$('.f_manager-alert').css('display', 'flex');

			// hide notification in 10 sec
			setTimeout(function() {
				$('.f_manager-alert').css('display', 'none');
			}, 5000);

			return false;
		});
	});
})(jQuery);