/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

	var $status_tag = $('.status-tag');

	// Change the status example's name when you change the name
	$('input[name="status"]').on('keyup', function(event) {
		var status = $(this).val() ? $(this).val() : EE.status.default_name;
		$status_tag.text(status);
	});

	$("input.color-picker").each(function() {
		new ColorPicker(this, {
			mode: 'both',
			swatches: ['E34834', 'F8BD00', '1DC969', '2B92D8', 'DE32E0', 'fff', '000'],
			onChange: function(newColor) {
				// Change background and border colors
				$status_tag.css('background-color', newColor).css('border-color', newColor);

				// Set foreground color
				var foregroundColor = new SimpleColor(newColor).fullContrastColor().hexStr;
				$status_tag.css('color', foregroundColor);
			}
		});
	});

});

})(jQuery);
