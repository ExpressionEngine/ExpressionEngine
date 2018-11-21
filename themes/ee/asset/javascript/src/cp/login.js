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

$(window).bind("onload", function() {

	// Reset button state in case user presses the back button
	// after a form submission
	$('input.btn').removeClass('work');
});

$(document).ready(function() {

	// Bind form submission to update button text
	$('form').submit(function(event) {

		var $button = $('input.btn', this);

		// Add "work" class to make the buttons pulsate
		$button.addClass('work');

		// Update the button text to the value of its "work-text"
		// data attribute
		if ($button.data('work-text') != '')
		{
			$button.attr('value', $button.data('work-text'));
		}
	});
});

})(jQuery);
