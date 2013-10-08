/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

$(document).ready(function () {
	var ajaxContentButtons = {},
		dialog_div = $('<div id=\"ajaxContent\" />'),
		msgBoxOpen, msgContainer, save_state, setup_hidden;

	ajaxContentButtons[EE.lang.close] = function () {
											$(this).dialog("close");
										};

	dialog_div.dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		position: "center",
		minHeight: "0", // fix display bug, where the height of the dialog is too big
		buttons: ajaxContentButtons
	});

	$("a.submenu").click(function () {
		if ($(this).data("working")) {
			return false;
		} else {
			$(this).data("working", true);
		}

		var url = $(this).attr("href"),
			that = $(this).parent(),
			submenu = that.find("ul"),
			dialog_title;

		if ($(this).hasClass("accordion")) {

			if (submenu.length > 0) {
				if (! that.hasClass("open")) {
					that.siblings(".open").toggleClass("open").children("ul").slideUp("fast");
				}

				submenu.slideToggle("fast");
				that.toggleClass("open");
			}

			$(this).data("working", false);
		}
		else {
			$(this).data("working", false);
			dialog_title = $(this).html();

			$("#ajaxContent").load(url + " .pageContents", function () {
				$("#ajaxContent").dialog("option", "title", dialog_title);
				$("#ajaxContent").dialog("open");
			});
		}
		return false;
	});
});
