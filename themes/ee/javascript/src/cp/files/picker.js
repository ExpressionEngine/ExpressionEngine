/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
/* This file exposes three callback functions:
 *
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow and
 * EE.manager.refreshPrefs
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {

	var picker_url;
	var modal;
	var input_value;
	var input_name;
	var input_img;

	$(document).ready(function () {
		$('.modal-file').on('click', 'a:not([href=""])', function(e) {
			e.preventDefault();
			$(this).parents('div.box').load($(this).attr('href'));
		});
		$('.filepicker').click(function (e) {
			picker_url = $(this).attr('href');
			input_value = $('input[name="' + $(this).data('input-value') + '"]');
			input_name = $('#' + $(this).data('input-name'));
			input_img = $('#' + $(this).data('input-image'));
			modal = $("." + $(this).attr('rel'));
			modal.find("div.box").load(picker_url);
		});
		$('.modal-file').on('click', 'tbody > tr', function(e) {
			var id = $(this).find("input[type='checkbox']").val();
			var file_url = picker_url.replace(/directory=.+(?=&)/ig, 'file=' + id);

			if (typeof EE.file_picker_callback != 'undefined')
			{
				callback = EE.file_picker_callback;
			} else {
				callback = function(data) {
					console.log(data);
					modal.find('.m-close').click();
					input_value.val(data.id);
					input_name.html(data.file_name);
					input_img.html("<img src='" + data.path + "' />");
				}
			}

			$.ajax({
				url: file_url,
				success: callback,
				dataType: 'json'
			});
		});
	});
})(jQuery);
