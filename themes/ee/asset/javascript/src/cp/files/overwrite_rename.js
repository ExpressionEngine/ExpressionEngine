/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.5.3
 * @filesource
 */

$(document).ready(function () {
	var confirmUnload = function (e) {
	    var e = e || window.event;
		var message = "The file name conflict has not been resolved.";

	    // For IE and Firefox prior to version 4
	    if (e) {
	        e.returnValue = message;
	    }

	    // For Safari
	    return message;
	};

	var cleanUp = function (e) {
		$.ajax({
			type: "POST",
			url: $('.w-12 .box form.settings').attr('action'),
			data: $('.w-12 .box form.settings').serialize() + '&submit=cancel',
			async: false
		});
	};

	$(window).on('beforeunload', confirmUnload);
	$(window).on('unload', cleanUp);

	$('.form-standard form').on('submit', function() {
		$(window).off('beforeunload', confirmUnload);
		$(window).off('unload', cleanUp);
	});
});
