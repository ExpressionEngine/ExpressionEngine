/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
$(document).ready(function () {
	"use strict";

	var ajaxContentButtons = {},
		dialog_div = $('<div id=\"ajaxContent\" />');

	ajaxContentButtons[EE.lang.close] = function() {
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
		}

		$(this).data("working", true);

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
