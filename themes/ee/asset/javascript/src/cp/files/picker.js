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
	var bind_modal = function(url, options) {
		var modal = $("." + options.rel),
			callback = function(data) {
				var picker = {
					modal: modal,
					input_value: options.input_value,
					input_name: options.input_name,
					input_img: options.input_img
				};
				options.callback(data, picker);
			};

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
			var id = $(this).data('id'),
				file_url = $(this).data('url'),
				current = $(this);

			current.data('selected', id);
			modal.find('tbody .selected').toggleClass('selected');
			options.selected = id;
			var selected = $(this);

			if (selected.prop("tagName") == 'A') {
				selected.parents('td').addClass('selected');
			} else {
				selected.parents('tr').addClass('selected');
			}

			if (options.ajax === false) {
				callback($(this));
			} else {
				$.ajax({
					url: file_url,
					success: function(data) {
						callback(data);
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

	$.fn.FilePicker = function(defaults) {
		this.off('click');

		return this.each(function() {
			$(this).on('click', function(){
				var options = {};

				// Duplicate the defaults object
				for (var property in defaults) {
					options[property] = defaults[property];
				}

				options.url = $(this).attr('href');
				options.rel = $(this).attr('rel');

				if (options.input_value) {
					options.input_value = $(options.input_value);
				} else {
					options.input_value = $('input[name="' + $(this).data('input-value') + '"], textarea[name="' + $(this).data('input-value') + '"]');
				}

				if (options.input_name) {
					options.input_name = $(options.input_name);
				} else {
					options.input_name = $('#' + $(this).data('input-name'));
				}

				if (options.input_img) {
					console.log('here?');
					options.input_img = $(options.input_img);
				} else {
					console.log($(this).data('input-image'));
					options.input_img = $('#' + $(this).data('input-image'));
				}

				if ( ! ('selected' in options)) {
					options.selected = $(this).data('selected');
				}

				bind_modal(options.url, options);
			});
		});
	};

	$(document).ready(function () {
		$('.filepicker').click(function (e) {
			var modal = $("." + $(this).attr('rel')),
				options = {
					"input_value": $('input[name="' + $(this).data('input-value') + '"], textarea[name="' + $(this).data('input-value') + '"]'),
					"input_name":  $('#' + $(this).data('input-name')),
					"input_img":   $('#' + $(this).data('input-image')),
					"selected":    $(this).data('selected'),
					"url":         $(this).attr('href')
				},
				callback_name = $(this).data('callback'),
				current = $(this);

			if (typeof callback_name != 'undefined' && callback_name.length !== 0)	{
				options.callback = function(data, picker) {
					var args = [data, picker],
						namespaces = callback_name.split("."),
						func = namespaces.pop(),
						context = window;

					for (var i = 0; i < namespaces.length; i++) {
						context = context[namespaces[i]];
					}

					return context[func].apply(this, args);
				};
			} else {
				options.callback = function(data, picker) {
					picker.modal.find('.m-close').click();
					picker.input_value.val(data.file_id);
					picker.input_name.html(data.file_name);
					picker.input_img.html("<img src='" + data.path + "' />");
				};
			}

			bind_modal(options.url, options);
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
