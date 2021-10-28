/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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
		$('a[rel=modal-view-file]').click(function (e) {
			var modal = $(this).attr('rel');
			$.ajax({
				type: "GET",
				url: EE.file_view_url.replace('###', $(this).data('file-id')),
				dataType: 'html',
				success: function (data) {
					$("." + modal + " div.box").html(data);
					SelectField.renderFields()
				}
			})
		});
	});
})(jQuery);
