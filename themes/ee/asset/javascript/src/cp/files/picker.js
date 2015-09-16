/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
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

	var modal;
	var current;

	var bind_modal = function(url, options) {
		$.get(url, function(data) {
			modal.find('div.box').html(data);
			if (typeof options.selected != 'undefined') {
				var selected = modal.find('tbody *[data-id="' + options.selected + '"]');
				selected.addClass('selected');

				if (selected.prop("tagName") == 'A') {
					selected.parents('td').addClass('selected');
				} else {
					selected.parents('tr').addClass('selected');
				}
			}
		});

		$('.modal-file').off('click', 'tbody > tr');
		$('.modal-file').on('click', ' .filepicker-item, tbody > tr', function(e) {
			e.stopPropagation();
			var id = $(this).data('id');
			var file_url = options.url.replace(/directory=.+(?=&)?/ig, 'file=' + id);

			current.data('selected', id);
			modal.find('tbody .selected').toggleClass('selected');
			options.selected = id;
			var selected = $(this);

			if (selected.prop("tagName") == 'A') {
				selected.parents('td').addClass('selected');
			} else {
				selected.parents('tr').addClass('selected');
			}

			if (options.ajax == false) {
				var picker = {
					modal: modal,
					input_value: options.input_value,
					input_name: options.input_name,
					input_img: options.input_img
				}
				options.callback($(this), picker);
				
			} else {
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
			}
		});
	};

	$.fn.FilePicker = function(options) {
		this.off('click');

		return this.each(function() {
			$(this).on('click', function(){
				current = $(this);
				options['url'] = $(this).attr('href');
				options['rel'] = $(this).attr('rel');

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

				if ( ! ('selected' in options)) {
					options['selected'] = $(this).data('selected');
				}
				modal = $("." + options.rel);
				bind_modal(options.url, options);
			});
		});
	};

	$(document).ready(function () {
		$('.modal-file').on('click', '.filters a:not([href=""]), .paginate a:not([href=""]), thead a:not([href=""]), .tbl-search a', function(e) {
			e.preventDefault();
			$(this).parents('div.box').load($(this).attr('href'));
		});
		$('.modal-file').on('submit', 'form', function(e) {
			e.preventDefault();
			$.ajax({
				type: "POST",
				url: $(this).attr('action'),
				data: $(this).serialize(),
				success: function(response) {
					$(this).parents('div.box').load(response);
				}
			});
		});
		$('.filepicker').click(function (e) {
			var options = {};
			options['input_value'] = $('input[name="' + $(this).data('input-value') + '"], textarea[name="' + $(this).data('input-value') + '"]');
			options['input_name'] = $('#' + $(this).data('input-name'));
			options['input_img'] = $('#' + $(this).data('input-image'));
			options['selected'] = $(this).data('selected');
			callback_name = $(this).data('callback');
			picker_url = $(this).attr('href');
			current = $(this);

			if (typeof callback_name != 'undefined' && callback_name.length !== 0)	{
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
			bind_modal(picker_url, options);
		});
	});
})(jQuery);
