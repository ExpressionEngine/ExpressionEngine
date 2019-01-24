/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
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

	$('input.color-picker').minicolors({
		changeDelay: 200,
		change: function (value, opacity) {
			// Change background and border colors
			$status_tag.css('background-color', value)
				.css('border-color', value);

			// Get foreground color
			$.post(
				EE.status.foreground_color_url,
				{highlight: value},
				function (data) {
					$status_tag.css('color', '#'+data);
				},
				'json'
			);
		}
	});

});

})(jQuery);
