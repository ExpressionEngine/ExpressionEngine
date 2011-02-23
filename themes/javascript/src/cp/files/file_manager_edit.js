/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

var crop = null,
	edit_mode = false,
	cropCoords,
	do_crop,
	crop_coords_array,
	$image = $('#file_manager_edit_file img'),
	oversized_class = 'oversized';

cropCoords = function (coords) {
	$("#crop_x").val(Math.floor(coords.x));
	$("#crop_y").val(Math.floor(coords.y));
	$("#crop_width").val(Math.floor(coords.w));
	$("#crop_height").val(Math.floor(coords.h));
};

function clearBoxes () {
	$("#crop_x").val("");
	$("#crop_y").val("");
	$("#crop_width").val(EE.filemanager.image_width);
	$("#crop_height").val(EE.filemanager.image_height);
	$("#resize_width").val(EE.filemanager.image_width);
	$("#resize_height").val(EE.filemanager.image_height);
}


$(document).ready(function () {

	// cancel cropping
	$('#cancel_crop').click(function () {

		if (crop !== undefined && crop !== null) {
			// destroy the crop object
			crop.destroy();
			crop = null;
		}
			
			// reset the crop form values
			cropCoords({
				'h': EE.filemanager.image_height,
				'w': EE.filemanager.image_width,
				'x': '',
				'y': ''
			});

		$('#toggle_crop').show();
		$('#cancel_crop').hide();	
		
		return false;
	});

	// crop
	$('#toggle_crop').click(function () {
		if (crop_coords_array === undefined) {
			crop_coords_array = [ 50, 50, 100, 100 ];
		}

		$('#toggle_crop').hide();
		$('#cancel_crop').show();

		crop = $.Jcrop('#file_manager_edit_file img', {
			setSelect: crop_coords_array,
			onChange: cropCoords,
			onSelect: function () {
				edit_mode = true;
			}
		});

		return false;		
	});
	
	$(".crop_dim").keyup(function () {
		// todo, finish
		$('#toggle_crop').hide();
		$('#cancel_crop').show();
	});

	EE.filemanager.resize_listener();
});

EE.filemanager.resize_listener = function() {
	var $resize_width = $('#resize_width'),
		$resize_height = $('#resize_height'),
		$cancel_button = $('#cancel_resize');
	
	$resize_width.add($resize_height).keyup(function(event) {
		// Enable cancel button
		$cancel_button.show();
		
		// Need to maintain proportions and resize image
		// In order to do this, I need to figure out ratio and adhere to it
		var $element = $(this),
			id = $element.attr('id'),
			$other_element = (id === "resize_height") ? $resize_width : $resize_height,
			image_ratio;
		
		// Determine ratio
		if (id === "resize_width") 
		{
			image_ratio = EE.filemanager.image_height / EE.filemanager.image_width;
			
		}
		else
		{
			image_ratio = EE.filemanager.image_width / EE.filemanager.image_height;
		}
		
		// Change other element's value
		$other_element.val(Math.round(image_ratio * $element.val()));
		
		if ($resize_width.val() > EE.filemanager.image_width) 
		{
			$resize_width.addClass(oversized_class);
		}
		else
		{
			$resize_width.removeClass(oversized_class);
		}
		
		if ($resize_height.val() > EE.filemanager.image_height) 
		{
			$resize_height.addClass(oversized_class);
		}
		else
		{
			$resize_height.removeClass(oversized_class);
		}
		
		// Resize image
		$image.attr({
			'width': $resize_width.val(),
			'height': $resize_height.val()
		});
	});
	
	$cancel_button.click(function(event) {
		event.preventDefault();
		
		$resize_width.val(EE.filemanager.image_width).removeClass(oversized_class);
		$resize_height.val(EE.filemanager.image_height).removeClass(oversized_class);
		
		$image.attr({
			'width': EE.filemanager.image_width,
			'height': EE.filemanager.image_height
		});
		
		$cancel_button.hide();
	});
};