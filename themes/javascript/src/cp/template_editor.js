/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

$(document).ready(function () {

	var accordion = $(".editAccordion"),
		template_data = $("#template_data");

	$(".button").css("float", "right");
	
	// Notes, Access, Prefs
	accordion.children("div").hide();
	accordion.children("h3").css("cursor", "pointer").addClass("collapsed").parent().addClass("collapsed");
	
	accordion.css("borderTop", $(".editAccordion").css("borderBottom"));
	
	accordion.children("h3").click(function () {
		var that = $(this);
		
		if (that.hasClass("collapsed")) {
			that.siblings().slideDown("fast");
			that.removeClass("collapsed").parent().removeClass("collapsed");
		}
		else {
			that.siblings().slideUp("fast");
			that.addClass("collapsed").parent().addClass("collapsed");
		}
	});
	
	accordion.filter(".open").find("h3").each(function () {
		$(this).siblings().show();
		$(this).removeClass("collapsed").parent().removeClass("collapsed");
	});
	
	template_data.markItUp(EE.template.markitup);
	
	// Just like calling focus(), but forces FF to move
	// the cursor to the beginning of the field
	template_data.createSelection(0, 0);
	
	EE.template_edit_url = EE.BASE + "&C=design&M=template_edit_ajax";
	EE.access_edit_url = EE.BASE + "&C=design&M=access_edit_ajax";

	$('#revisions').submit(
		function () {
			var id = $("#revision_id").val();

			if (id === "clear") {
				window.open(EE.template.url + id, "Revision", "height=350, location=0, menubar=0, resizable=0, scrollbars=0, status=0, titlebar=0, toolbar=0, screenX=60, left=60, screenY=60, top=60");
			} else if (id != "0") {
				window.open(EE.template.url + id, "Revision");
			}
			return false;
		}
	);
});