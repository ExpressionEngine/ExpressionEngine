/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
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

	var bind_modal = function(options) {
		$('.modal-file').off('click', 'tbody > tr');
		$('.modal-file').on('click', 'tbody > tr', function(e) {
			var id = $(this).find("input[type='checkbox']").val();
			var file_url = options.url.replace(/directory=.+(?=&)/ig, 'file=' + id);

			$.ajax({
				url: file_url,
				success: function(data) {
					var picker = {
						modal: modal,
						input_value: options.input_value,
						input_name: options.input_name,
						input_img: options.input_img
					}
					options.callback(data, picker);
				},
				dataType: 'json'
			});
		});
	};

	$.fn.FilePicker = function(options) {
		this.off('click');
		options['url'] = this.attr('href');
		options['rel'] = this.attr('rel');

		if (options.input_value) {
			options['input_value'] = $(options.input_value);
		} else {
			options['input_value'] = $('input[name="' + $(this).data('input-value') + '"], textarea[name="' + $(this).data('input-value') + '"]');
		}

		if (options.input_name) {
			options['input_name'] = $(options.input_name);
		} else {
			options['input_name'] = $('#' + $(this).data('input-name'));
		}

		if (options.input_img) {
			options['input_img'] = $(options.input_img);
		} else {
			options['input_img'] = $('#' + $(this).data('input-image'));
		}

		return this.each(function() {
			$(this).on('click', function(){
				modal = $("." + options.rel);
				modal.find("div.box").load(options.url);
				bind_modal(options);
			});
		});
	};

	$(document).ready(function () {
		$('.modal-file').on('click', 'a:not([href=""])', function(e) {
			e.preventDefault();
			$(this).parents('div.box').load($(this).attr('href'));
		});
		$('.filepicker').click(function (e) {
			var options = {};
			options['input_value'] = $('input[name="' + $(this).data('input-value') + '"], textarea[name="' + $(this).data('input-value') + '"]');
			options['input_name'] = $('#' + $(this).data('input-name'));
			options['input_img'] = $('#' + $(this).data('input-image'));
			callback_name = $(this).data('callback');
			picker_url = $(this).attr('href');

			if (callback_name.length !== 0)	{
				callback = function(data, picker) {
					var args = [data, picker];
					var namespaces = callback_name.split(".");
					var func = namespaces.pop();
					var context = window;

					for(var i = 0; i < namespaces.length; i++) {
						context = context[namespaces[i]];
					}

					return context[func].apply(this, args);
				};
			} else {
				callback = function(data, picker) {
					picker.modal.find('.m-close').click();
					picker.input_value.val(data.file_id);
					picker.input_name.html(data.file_name);
					picker.input_img.html("<img src='" + data.path + "' />");
				}
			}

			options['url'] = picker_url;
			options['callback'] = callback;
			modal = $("." + $(this).attr('rel'));
			modal.find("div.box").load(picker_url);
			bind_modal(options);
		});
	});
})(jQuery);
