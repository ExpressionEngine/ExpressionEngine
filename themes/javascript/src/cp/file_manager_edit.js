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
	editing = false,
	show_coors;



show_coors = function (coords) {
	$("#crop_x").val(Math.floor(coords.x));
	$("#crop_y").val(Math.floor(coords.y));
	$("#crop_width").val(Math.floor(coords.w));
	$("#crop_height").val(Math.floor(coords.h));
};


$(document).ready(function () {

	// cancel cropping
	$('#cancel_crop').click(function () {		

		if (crop !== null && editing === true) {
			// destroy the crop object
			crop.destroy();
			crop = null;
			
			// reset the crop form values
			show_coors({
				'h': EE.filemanager.image_height,
				'w': EE.filemanager.image_width,
				'x': '',
				'y': '',
			});

			$('#toggle_crop').parent('li').show();
			$('#save_crop').parent('li').hide();
			$('#cancel_crop').parent('li').hide();	
		}
		
		return false;
	});
	
	// Save Crop
	$('#save_crop').click(function () {
		
		
		return false;
	});


	// crop
	$('#toggle_crop').click(function () {

		editing = true;
		
		$('#toggle_crop').parent('li').hide();
		$('#save_crop').parent('li').show();
		$('#cancel_crop').parent('li').show();
		
		crop = $.Jcrop('#file_manager_edit_file img', {
			onChange: show_coors,
			onSelect: show_coors
		});
						
		return false;		
	});

});