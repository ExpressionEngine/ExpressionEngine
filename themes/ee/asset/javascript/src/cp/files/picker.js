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
	var callback;

	var bind_modal = function(url, options) {
		callback = function(data) {
			var picker = {
				modal: modal,
				input_value: options.input_value,
				input_name: options.input_name,
				input_img: options.input_img
			}
			options.callback(data, picker);
		}
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

		$('.modal-file').off('click', '.filepicker-item, tbody > tr');
		$('.modal-file').on('click', '.filepicker-item, tbody > tr', function(e) {
			e.stopPropagation();
			var id = $(this).data('id');
			var file_url = $(this).data('url');

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

		$('.modal-file').on('click', '.filters a:not([href=""]), .paginate a:not([href=""]), thead a:not([href=""])', function(e) {
			e.preventDefault();
			$(this).parents('div.box').load($(this).attr('href'));
		});
		$('.modal-file').on('click', '.tbl-search a', function(e) {
			e.preventDefault();
			$('div.box', modal).html("<iframe></iframe>");
			$('iframe', modal).css('border', 'none');
			$('iframe', modal).css('width', '100%');
			$('iframe', modal).load(function (e) {
				var response = $(this).contents().find('body');
				var responseWindow = response;
				responseWindow.hide();

				if ($(response).find('pre').length)
				{
					response = $(response).find('pre');
				}

				response = response.html();

				try {
					response  = JSON.parse(response);
					callback(response);
				} catch(e) {
					responseWindow.show();
					var height = $(this).contents().find('body').height();
					$('.box', modal).height(height);
			    	$(this).height(height);
				}
			});
			$('iframe', modal).attr('src', $(this).attr('href'));
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
		modal = $("." + $(this).attr('rel'));
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
			bind_modal(picker_url, options);
		});
	});

})(jQuery);

function loadSettingsModal(modal, data) {
	$('div.box', modal).html(data);

	// Bind validation
	EE.cp.formValidation.init(modal);

	$('form', modal).on('submit', function() {
		$.ajax({
			type: 'POST',
			url: this.action,
			data: $(this).serialize()+'&save_modal=yes',
			dataType: 'json',

			success: function(result) {
				console.log(result);
				if (result.messageType == 'success') {
					modal.trigger('modal:close');
				} else {
					loadSettingsModal(modal, result.body);
				}
			}
		});

		return false;
	});
}
