/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

$(document).ready(function () {
	$("#myaccountHtmlButtonsLink").show(); // JS only feature, its hidden by default

	$(".mainTable .tag_order input").hide();

	$(".mainTable tbody").sortable(
		{
			axis: "y",
			containment: "parent",
			placeholder: "tablesize",
			start: function () {
				$(".submit input.submit").attr("disabled", true).addClass("disabled_field");
			},
			stop: function () {
				var tag_order = "";
				$(".mainTable input.tag_order").each(function () {
					tag_order += "&" + $(this).attr("name") + "=" + $(this).val();
				});
				$.ajax({
					type: "POST",
					url: EE.BASE + "&C=myaccount&M=reorder_html_buttons",
					data: "XID=" + EE.XID + tag_order,
					complete: function () {
						$(".submit input.submit").attr("disabled", false).removeClass("disabled_field");
					},
					success: function () {
						$(".tag_order input[type=text]").each(function (i) {
							$(this).val(i);
						});
					}
				});
			}
		}
	);

	$(".del_row").show(); // js only functionality

	$(".del_row a").click(function () {
		// remove the button from the db
		$.ajax({url: $(this).attr("href")});

		// remove it from the table
		$(this).parent().parent().remove();

		// stay here
		return false;
	});

	$("#add_new_html_button").hide();
	$(".del_instructions").hide();

	$(".cp_button").show().toggle(function () {
		$("#add_new_html_button").slideDown();
	}, function () {
		$("#add_new_html_button").slideUp();
	});
});