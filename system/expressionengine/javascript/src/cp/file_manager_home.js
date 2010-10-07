/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

$.tablesorter.addParser({ 
	id: 'filesize', 
	is: function (s) {
		return false; 
	}, 
	format: function (s) { 
		s = s.replace(/[^0-9.]/g, '');
		return s * 1000;
	}, 
	type: 'numeric' 
});


$(document).ready(function () {

	$(".mainTable").tablesorter({
		headers: {
			1: {sorter: "filesize"},
			4: {sorter: false},
			5: {sorter: false},
			6: {sorter: false}
		},
		widgets: ["zebra"],
		sortList: [[0, 0]] 
	});
	
	$("#file_tools").show();
	
	$("#download_selected").css("display", "block");

	function show_file_info(file) {
		var file_info_hold, file_info_header;
		
		file_info_hold   = $("#file_information_hold");
		file_info_header = $("#file_information_header");
		
		file_info_header.removeClass("closed");
		file_info_header.addClass("open");

		file_info_hold.slideDown("fast");
		file_info_hold.html('<p style="text-align: center;"><img src="' + EE.THEME_URL + 'images/indicator.gif" alt="' + EE.lang.loading + '" /><br />' + EE.lang.loading + '...</p>');

		$.get(EE.BASE + "&C=content_files&M=file_info",
			{file: file},
			function (data) {
				file_info_hold.html(data);
			}
		);
	}

	$("#showToolbarLink a").toggle(
		function () {
			$("#file_manager_tools").hide();
			$("#showToolbarLink a span").text(EE.lang.show_toolbar);
			$("#showToolbarLink").animate({
				marginRight: "20"
			});
			$("#file_manager_holder").animate({
				marginRight: "10"
			});
			
			// Swap the image
			$("#hideToolbarImg").hide();
			$("#showToolbarImg").css("display", "inline");	// .show() uses block

		}, function () {
			$("#showToolbarLink a span").text(EE.lang.hide_toolbar);
			$("#showToolbarLink").animate({
				marginRight: "264"
			});
			$("#file_manager_holder").animate({
				marginRight: "250"
			}, function () {
				$("#file_manager_tools").show();
				
				// Swap the image
				// Doing after the animation in this step as the header background won't show up to
				// that point, and the hide image blends in with that header. Looks strange without it.
				$("#showToolbarImg").hide();
				$("#hideToolbarImg").css("display", "inline");	// .show() uses block
			});
		}
	);

	$("#file_manager_tools h3 a").toggle(
		function () {
			$(this).parent().next("div").slideUp();
			$(this).toggleClass("closed");
		}, function () {
			$(this).parent().next("div").slideDown();
			$(this).toggleClass("closed");
		}
	);

	$("#file_manager_list h3").toggle(
		function () {
			document.cookie = "exp_hide_upload_" + $(this).next().attr("id") + "=true";
			$(this).next().slideUp();
			$(this).toggleClass("closed");
		}, function () {
			document.cookie = "exp_hide_upload_" + $(this).next().attr("id") + "=false";
			$(this).next().slideDown();
			$(this).toggleClass("closed");
		}
	);

	// collapse sidebar and folder list by default
	$("#file_manager_tools h3.closed").next("div").hide();
	$("#file_manager_tools h3.closed a").click();

	function upload_fail(message)
	{
		// change status and fade it out
		$("#progress").html("<span class=\"notice\">" + message + "</span>");
	}

	function setup_events() {
		$("td.overlay a").unbind("click").
			overlay({
				mask: {
					color: '#ebecff',
					loadSpeed: 200,
					opacity: 1.0
				},
				closeOnClick: true,
				onBeforeLoad: function () { 
					var wrap = this.getOverlay().find('.contentWrap');
					$('.contentWrap img').remove();
					$('<img />', {'src': this.getTrigger().attr('href')}).appendTo(wrap);
				}
			}).
			click(function () {	
				show_file_info($(this).parent().attr("id"));
			}
		);

		// Set the row as "selected"
		$(".toggle").unbind("click").click(function (e) {
			$(this).parent().parent().toggleClass("selected");
		});

		$(".mainTable td").unbind("click").click(function (e) {
			// if the control or command key was pressed, select the file
			if (e.ctrlKey || e.metaKey) {
				$(this).parent().toggleClass("selected"); // Set row as selected

				if (! $(this).parent().find(".file_select :checkbox").attr("checked")) {
					$(this).parent().find(".file_select :checkbox").attr("checked", "true");
				} else {
					$(this).parent().find(".file_select :checkbox").attr("checked", "");
				}
			}
		});
	}

	$("input[type=file]").ee_upload({
		url: EE.BASE + "&C=content_files&M=upload_file&is_ajax=true",
		onStart: function (el) {
			$("#progress").html('<p><img src="' + EE.THEME_URL + 'images/indicator.gif" alt="' + EE.lang.loading + '" />' + EE.lang.uploading_file + '</p>').show();
			
			var dir_id = $("#upload_dir").val();
			return {upload_dir: dir_id};
		},
		onComplete: function (res, el, opt) {
			if (typeof(res) === "object") {
				if (res.success) {
					var directory_container = "#dir_id_" + opt.upload_dir,
			
					// @confirm this is a bit ugly - cannot think of an easy way to send this as part of the
					// response without forcing a layout
					 refresh_url = EE.BASE + "&C=content_files&ajax=true&directory=" + opt.upload_dir + "&enc_path=" + res.enc_path;
			
					$.get(refresh_url, function (response) {
						var tmp = $("<div />"),
							sorting = [[0, 0]]; // Reset sort to force re-stripe
							
						tmp.append(response);
						tmp = tmp.find("tbody tr");
			
						$(directory_container + " tbody").append(tmp);
			
						// remove row with warning message if its there
						$(directory_container + " tbody .no_files_warning").parent().remove();
			
						// let the tablesorter plugin know that we have an update
						$(directory_container + " table").trigger("update");
			
			
						$("table").trigger("sorton", [sorting]);
			
						setup_events();
			
						$("#progress").html(res).slideUp("slow");
					}, "html");
					
				} else {
					upload_fail(res.error);
				}
			}
		}
	});



	// tools
	$("#download_selected a").click(function () {
		var action = $("#files_form").attr("action");
			
		$("#files_form").attr("action", action.replace(/delete_files_confirm/, "download_files"));
		$("#files_form").submit();
		
		return false;
	});

	$("a#email_files").click(function () {
		alert("not yet functional");
		return false;
	});  


	$("#delete_selected_files a").click(function () {
		// these may be been downloaded: ensure the action attr is correct
		var action = $("#files_form").attr("action");
			
		$("#files_form").attr("action", action.replace(/download_files/, "delete_files_confirm"));
		$("#files_form").submit();
		
		return false;
	});


	$(".toggle_all").toggle(
		function () {
			$(this).closest("table").find("tbody tr").addClass("selected");
			$(this).closest("table").find("input.toggle").attr('checked', true);
		}, function () {
			$(this).closest("table").find("tbody tr").removeClass("selected");
			$(this).closest("table").find("input.toggle").attr('checked', false);
		}
	);

	$("input.toggle").each(function () {
		this.checked = false;
	});


	setup_events();	
	
});
