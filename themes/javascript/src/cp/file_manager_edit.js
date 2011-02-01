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
	edit_mode = false;

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
	$("#file_manager_toolbar").accordion({autoHeight: false,header: "h3"});

	// cancel cropping
	$('#cancel_crop').click(function () {

		if (crop !== undefined) {
			// destroy the crop object
			crop.destroy();
			crop = null;
			
			// reset the crop form values
			cropCoords({
				'h': EE.filemanager.image_height,
				'w': EE.filemanager.image_width,
				'x': '',
				'y': ''
			});

			$('#toggle_crop').parent('li').show();
			$('#cancel_crop').parent('li').hide();	
			
			// Update action form input
			$('#image_edit_form input[name=action]').val('');
		}
		
		return false;
	});

	// crop
	$('#toggle_crop').click(function () {
		if (crop_coords_array === undefined) {
			crop_coords_array = [ 50, 50, 100, 100 ];
		}

		$('#toggle_crop').parent('li').hide();
		$('#cancel_crop').parent('li').show();

		// Update action form input
		$('#image_edit_form input[name=action]').val('crop');

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

	});
	
	

});