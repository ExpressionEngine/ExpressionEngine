/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('.js-copy-url-button').on('click', function (e) {
			e.preventDefault();
			// copy asset link to clipboard
			var copyText = $(this).attr('href');

			document.addEventListener('copy', function(e) {
				e.clipboardData.setData('text/plain', copyText);
				e.preventDefault();
			}, true);

			document.execCommand('copy');

			// show notification
			$('.f_manager-alert').css('display', 'flex');
			DropdownController.hideAllDropdowns()

			// hide notification in 10 sec
			setTimeout(function() {
				$('.f_manager-alert').css('display', 'none');
			}, 5000);

			return false;
		});

		$('body').on('click', '.f_manager-action-part [data-conditional-modal="confirm-trigger"]', function(e) {
			var data_element = $(this).data('conditional-modal');
			var select = $('*[data-' + data_element + ']').closest('select').get(0);
			var conditional_element = $(select.options[select.selectedIndex]);

			if ($(conditional_element).val() == 'copy_link') {
				var checked = $(this).parents('form').find('th input:checked, td input:checked, li input:checked, .file-grid__file input:checked');
				console.log('checked', checked);
				var copyText = $(checked).data('link');

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
			}

			return false;
		})
	});
})(jQuery);