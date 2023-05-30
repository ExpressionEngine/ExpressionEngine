/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) { "use strict";

	function zeroPad(number) {
		number = number.toString();
		if (number.length < 2) {
			return '0' + number;
		}

		return number;
	}

	EE.formatDate = function(date, config) {

		date = date || new Date();
		config = $.extend({
			time_format: '12',
			include_seconds: 'n'
		}, config);

		var hours = date.getHours(),
			minutes = zeroPad(date.getMinutes()),
			seconds = zeroPad(date.getSeconds()),
			segments = [],
			suffix = '';

		if (config.time_format == "12") {
			suffix = (hours < 12) ? ' AM': ' PM';
			hours = hours % 12 || 12;
		}

		segments.push(hours);
		segments.push(minutes);

		if (config.include_seconds == 'y') {
			segments.push(seconds);
		}

		return " '" + segments.join(':') + suffix + "'";
	};

	EE.date_obj_time = EE.formatDate(new Date(), EE.date);

})(jQuery);
