/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/*!
 * ExpressionEngine File Manager
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
$(document).ready(function() {

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
		function(){
			$("#file_manager_tools").hide();
			$("#showToolbarLink a span").text(EE.lang.show_toolbar);
			$("#showToolbarLink").animate({
				marginRight: "20"
			});
			$("#file_manager_holder").animate({
				marginRight: "10"
			});
		}, function (){
			$("#showToolbarLink a span").text(EE.lang.hide_toolbar);
			$("#showToolbarLink").animate({
				marginRight: "314"
			});
			$("#file_manager_holder").animate({
				marginRight: "300"
			}, function(){
				$("#file_manager_tools").show();
			});
		}
	);

	$("#file_manager_tools h3 a").toggle(
		function(){
			$(this).parent().next("div").slideUp();
			$(this).toggleClass("closed");
		}, function(){
			$(this).parent().next("div").slideDown();
			$(this).toggleClass("closed");
		}
	);

	$("#file_manager_list h3").toggle(
		function(){
			$(this).next().slideUp();
			$(this).toggleClass("closed");
		}, function(){
			$(this).next().slideDown();
			$(this).toggleClass("closed");
		}
	);

	function cropCoords(coords)
	{
		$("#crop_x").val(Math.floor(coords.x));
		$("#crop_y").val(Math.floor(coords.y));
		$("#crop_width").val(Math.floor(coords.w));
		$("#crop_height").val(Math.floor(coords.h));
	};

	function clearBoxes(reveal)
	{
		$(".edit_option").hide();

		if (reveal != undefined)
		{
			$("#"+reveal+"_fieldset").fadeIn();
		}

		$("#crop_x").val("");
		$("#crop_y").val("");
		$("#crop_width").val("");
		$("#crop_height").val("");
		$("#resize_width").val("");
		$("#resize_height").val("");
	}

	function resizeImage(size)
	{
		if (size == undefined){
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

	function confirm(message, callback_true, callback_false) {
		$("#confirm").modal({
			close:false, 
			overlayId:"confirmModalOverlay",
			containerId:"confirmModalContainer", 
			onShow: function (dialog) {
				dialog.data.find(".message").append(message);

				// if the user clicks "yes"
				dialog.data.find(".yes").click(function () {
					// call the callback
					if ($.isFunction(callback_true)) {
						callback_true.apply();
					}

					// close the dialog
					$.modal.close();
				});

				// if the user clicks "no"
				dialog.data.find(".no").click(function () {
					// call the callback
					if ($.isFunction(callback_false)) {
						callback_false.apply();
					}

					// close the dialog
					$.modal.close();
				});
			}
		});
	}

	// edit_mode is simply a flag to tell if the user has started editing anything at this time
	// its use is really just to prevent the confirm dialog from popping up if they switched to
	// an edit mode, but did not use it
	edit_mode = false;

	function confirm_win(mode)
	{
		if (edit_mode != false)
		{
			confirm(
				"'.$this->lang->line('exit_apply_changes').'", 
				function () {
					// "true" function, used if they say "yes"
	//				$("#image_edit_form").submit(); 	// forcing the submit is not working oddly
					$("#edit_file_submit").click(); 	//we click the submit button instead
					return true;
				},
				function () {
					// "false" function, used if they say "no"
					change_mode(mode);
				}
			);
		}
		else
		{
			change_mode(mode);
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

	function change_mode(mode, crop_coords_array)
	{
		if (crop_coords_array == undefined)
		{
			crop_coords_array = [ 50, 50, 100, 100 ];
		}
		
		$("#edit_image").resizable("destroy"); // turn off resize
		$("#edit_image_holder").html('<img src="'+EE.filemanager.url_path+'" alt="" id="edit_image" />'); // replace image

		if (mode == "rotate")
		{
			clearBoxes("rotate");
		}
		else if (mode == "resize")
		{
			setTimeout(resize_sleep, 250);
		}
		else
		{
			clearBoxes("crop");
			$("#edit_image").Jcrop({
				setSelect: crop_coords_array,
				onChange: cropCoords,
				onSelect: function(){edit_mode = true;}
			});
		}
	}

	var image_ratio_width = $("#edit_image").height()/$("#edit_image").width(),
		image_ratio_height = $("#edit_image").width()/$("#edit_image").height();

	$("#rotate_fieldset li img").click(function() {
		var rotate_type = $(this).parent('li').attr('class').substr(7);
		
		$("p.last select#rotate").val(rotate_type);

		// We will submit the form for them. While this is happening, we do not
		// want them to click the submit button manually, so we disable and enable it
		$("#edit_file_submit").attr("disabled", true).addClass("disabled_field");
		$("#image_edit_form").submit();
	}, function() {
		$("#edit_file_submit").attr("disable", "false").removeClass("disabled_field");
	});

	function changeDimValue(dim, master_dim)
	{
/*
	//	var max 	= (side == "h") ? <?php echo $max_w; ?>	: <?php echo $max_h; ?>;
		var max 	= (side == "h") ? 800 : 600;
		var unit	= "pixels"; //(side == "w") ? f.width_unit	: f.height_unit;
		var orig	= (side == "w") ? f.width_orig	: f.height_orig;
		var curr	= (side == "w") ? f.width 		: f.height;
		var t_unit	= "pixels"; //(side == "h") ? f.width_unit	: f.height_unit;
		var t_orig	= (side == "h") ? f.width_orig	: f.height_orig;
		var t_curr	= (side == "h") ? f.width		: f.height;

		var res = Math.floor((curr.value/orig.value) * t_orig.value);

		if (res > max)
		{
			t_curr.value = t_orig.value;

			curr.value	 = Math.min(curr.value, orig.value);
		}
		else
		{
			t_curr.value = res;
		}
*/
		var max = 800;
		ratio = (master_dim == "height") ? image_ratio_height : image_ratio_width;
		result = Math.floor(ratio * dim);
		return result;
	}
	
	
	$("#crop_mode").click(function(){
		
			confirm_win("crop");
		
		return false;
	});

	$("#resize_mode").click(function(){
		
			confirm_win("resize");
		
		return false;
	});

	$(".crop_dim").keyup(function(){
		
			change_mode("crop", [$("#crop_x").val(), $("#crop_y").val(), parseInt($("#crop_x").val())+parseInt($("#crop_width").val()), parseInt($("#crop_y").val())+parseInt($("#crop_height").val())]);
		
	});

	$("#resize_width").keyup(function(){
		
			width = parseInt($("#resize_width").val());
			height = changeDimValue(width, "width");

			$("#edit_image, .ui-wrapper").width(width);
			$("#edit_image, .ui-wrapper").height(height);
			$("#resize_height").val(height);
		
	});

	$("#resize_height").keyup(function(){
		
			height = parseInt($("#resize_height").val());
			width = changeDimValue(height, "height");

			$("#edit_image, .ui-wrapper").width(width);
			$("#edit_image, .ui-wrapper").height(height);
			$("#resize_width").val(width);
		
	});

	$("#rotate_mode").click(function(){
		
			confirm_win("rotate");
		
		return false;
	});

	$("#rotate").change(function(){
		
			edit_mode = true;
		
	});
});