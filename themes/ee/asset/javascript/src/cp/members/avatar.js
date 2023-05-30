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

		// Avatar delete button
		$('li.remove a').click(function (e) {
			$(this).closest('figure').find('input[type="hidden"]').val('');
			$(this).closest('fieldset').hide();
			e.preventDefault();
		});

	});
})(jQuery);
