/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
/* This file exposes three callback functions:
 *
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow and
 * EE.manager.refreshPrefs
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('table .toolbar .settings a').click(function (e) {
			var modal = $(this).attr('rel');
			$.ajax({
				type: "GET",
				url: EE.template_settings_url.replace('###', $(this).data('template-id')),
				dataType: 'html',
				success: function (data) {
					$("." + modal + " div.box").html(data);

					// Bind validation
					EE.cp.formValidation.init($("." + modal + " div.box form"));
				}
			})
		});
	});
})(jQuery);
