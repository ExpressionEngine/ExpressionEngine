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

$(document).ready(function () {

	// Some page setup
	// hide the detailed data
	$(".edit_option").hide();

	// if this is read, then js is enabled... set up the ajax-y goodness...
	//$("#ajax").val("TRUE");

	// if JS is enabled, switch to icons for rotate by hiding the 
	// dropdown and revealing the list of icons (hidden via css)
	$("#rotate_fieldset p").hide();
	$("#rotate_fieldset .icons").show();
	
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
			$(this).next().slideUp();
			$(this).toggleClass("closed");
		}, function () {
			$(this).next().slideDown();
			$(this).toggleClass("closed");
		}
	);

	function cropCoords(coords) {
		$("#crop_x").val(Math.floor(coords.x));
		$("#crop_y").val(Math.floor(coords.y));
		$("#crop_width").val(Math.floor(coords.w));
		$("#crop_height").val(Math.floor(coords.h));
	}

	function clearBoxes(reveal)
	{
		$(".edit_option").hide();

		if (reveal !== undefined)
		{
			$("#" + reveal + "_fieldset").fadeIn();
		}

		$("#crop_x").val("");
		$("#crop_y").val("");
		$("#crop_width").val("");
		$("#crop_height").val("");
		$("#resize_width").val("");
		$("#resize_height").val("");
	}

	function confirm(message, callback_true, callback_false) {
		var buttons = {};
		
		buttons[EE.lang.no] = function () {
			// call the callback
			if ($.isFunction(callback_false)) {
				callback_false.apply();
			}

			$(this).dialog('close');
		};
		
		buttons[EE.lang.apply_changes] = function () {
			// call the callback
			if ($.isFunction(callback_true)) {
				callback_true.apply();
			}			
			
			$(this).dialog('close');
		};
		
		$('#confirm').dialog({
			buttons: buttons,
			open: function (event, ui) {
				$(this).find('div').html(message);
			},
			modal: true
		});
	}
	
	// edit_mode is simply a flag to tell if the user has started editing anything at this time
	// its use is really just to prevent the confirm dialog from popping up if they switched to
	// an edit mode, but did not use it
	var edit_mode = false,
		image_ratio_width = $("#edit_image").height() / $("#edit_image").width(),
		image_ratio_height = $("#edit_image").width() / $("#edit_image").height();

	function resizeImage(size)
	{
		if (size === undefined) {
			$("#resize_width").val($("#edit_image").width());
			$("#resize_height").val($("#edit_image").height());
		}
		else
		{
			edit_mode = true; // just a global var to indicate if its the first time we hit this..

			$("#resize_width").val(Math.floor(size.width));
			$("#resize_height").val(Math.floor(size.height));
		}
	}


	// OK, lets explain this. Some browsers (chrome) fire off too quickly, and the ui
	// information is not available in time, resulting in a width and height of zero.
	// This is just a work around.
	function resize_sleep()
	{
		clearBoxes("resize");

		resizeImage(); // reset boxes

		$("#edit_image").resizable({ 
			handles: "all",
			animate: true, 
			ghost: true,
			aspectRatio: true,
			knobHandles: true,
			resize: function (e, ui) {
				resizeImage(ui.size);
			}
		});
	}

	function change_mode(mode, crop_coords_array) {
		if (crop_coords_array === undefined) {
			crop_coords_array = [ 50, 50, 100, 100 ];
		}
		
		$("#edit_image").resizable("destroy"); // turn off resize
		$("#edit_image_holder").html('<img src="' + EE.filemanager.url_path + '" alt="" id="edit_image" />'); // replace image

		if (mode === "rotate")
		{
			clearBoxes("rotate");
		}
		else if (mode === "resize")
		{
			setTimeout(resize_sleep, 250);
		}
		else
		{
			clearBoxes("crop");
			$("#edit_image").Jcrop({
				setSelect: crop_coords_array,
				onChange: cropCoords,
				onSelect: function () {
					edit_mode = true;
				}
			});
		}
	}

	function confirm_win(mode) {
		
		console.log(edit_mode);
		
		if (edit_mode !== false) {
			confirm(
				EE.lang.exit_apply_changes,
				function () {
					// "true" function, used if they say "yes"
					$("#edit_file_submit").click();	//we click the submit button instead
					return true;
				}, function () {
					// "false" function, used if they say "no"
					change_mode(mode);
				}
			);
		} else {
			change_mode(mode);
		}
	}

	$("#rotate_fieldset li img").click(function () {
		var rotate_type = $(this).parent('li').attr('class').substr(7);
		
		$("p.last select#rotate").val(rotate_type);

		// We will submit the form for them. While this is happening, we do not
		// want them to click the submit button manually, so we disable and enable it
		$("#edit_file_submit").attr("disabled", true).addClass("disabled_field");
		$("#image_edit_form").submit();
	}, function () {
		$("#edit_file_submit").attr("disable", "false").removeClass("disabled_field");
	});

	function changeDimValue(dim, master_dim) {
		var max = 800,
			ratio = (master_dim === "height") ? image_ratio_height : image_ratio_width,
			result = Math.floor(ratio * dim);
		return result;
	}
	
	
	$("#crop_mode").click(function () {
		confirm_win("crop");
		return false;
	});

	$("#resize_mode").click(function () {
		confirm_win("resize");
		return false;
	});

	$(".crop_dim").keyup(function () {		
		change_mode("crop", 
					[$("#crop_x").val(), 
					$("#crop_y").val(), 
					parseInt($("#crop_x").val()) + parseInt($("#crop_width").val()), 
					parseInt($("#crop_y").val()) + parseInt($("#crop_height").val())]
				);
		
	});

	$("#resize_width").keyup(function () {
		var width = parseInt($("#resize_width").val()),
			height = changeDimValue(width, "width");

		$("#edit_image, .ui-wrapper").width(width);
		$("#edit_image, .ui-wrapper").height(height);
		$("#resize_height").val(height);
		
	});

	$("#resize_height").keyup(function () {
		var height = parseInt($("#resize_height").val()),
			width = changeDimValue(height, "height");

		$("#edit_image, .ui-wrapper").width(width);
		$("#edit_image, .ui-wrapper").height(height);
		$("#resize_width").val(width);		
	});

	$("#rotate_mode").click(function () {
		confirm_win("rotate");
		return false;
	});

	$("#rotate").change(function () {
		var edit_mode = true;
	});
});