/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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
					input_img: options.input_img,
					source: options.source
				};
				options.callback(data, picker);
			};

		if (options.iframe) {
			return openInIframe(url)
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

		$('.modal-file').off('click', '.filepicker-item, tbody > tr:not(.tbl-action)');
		$('.modal-file').on('click', '.filepicker-item, tbody > tr:not(.tbl-action)', function(e) {

			if ($(e.target).is('a[rel=external]')) {
				return true;
			}

			e.stopPropagation();
			var id = $(this).data('id'),
				file_url = $(this).data('url'),
				current = $(this);

			current.data('selected', id);
			modal.find('tbody .selected').toggleClass('selected');
			options.selected = id;
			options.source.data('selected', id);

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
			var new_url = $(this).attr('href');

			$(this).parents('div.box').load(new_url);
			if ($(options.source).hasClass('markItUpButton') || $(options.source).hasClass('rte-upload')) {
				$('.publish .toolbar.rte li.m-link[rel="modal-file"], .publish .toolbar.html-btns li.m-link[rel="modal-file"]').attr('href', new_url);
			}
		});
		// Capture form submission
		$('.modal-file').on('submit', 'form', function(e) {
			var url = $(this).attr('action'),
				payload_elements = $('input[name=search], input[name=perpage]', this);

			// Only do this if we're on the file listing screen
			if (payload_elements.size() == 0) {
				return;
			}

			e.preventDefault();

			$(this).parents('div.box').load(url+'&'+payload_elements.serialize());
		});
		$('.modal-file').on('click', '.tbl-action .action', function(e) {
			e.preventDefault()
			openInIframe($(this).attr('href'))
		});

		function openInIframe(url) {
			$('div.box', modal).html("<iframe></iframe>");

			var frame = $('iframe', modal);
			frame.css('border', 'none');
			frame.css('width', '100%');

			// bind an unload event on the frame that hides it
			// this prevents a flash of json when uploading
			var bindFrameUnload = function() {
				$(frame[0].contentWindow).on('unload', function() {
					frame.hide();
					$('.box', modal).height('auto');
					$(modal).height('auto');
				});
			};

			var cancelOnClose = function() {
				$.ajax({
					type: "POST",
					url: $(frame).contents().find('form').attr('action'),
					data: $(frame).contents().find('form').serialize() + '&submit=cancel',
					async: false
				});
			}

			frame.load(function (e) {
				$(modal).off('modal:close', cancelOnClose);

				var response = $(this).contents().find('body');

				SelectField.renderFields(response)

				if ($(response).find('pre').length)
				{
					response = $(response).find('pre');
				}

				response = response.html();

				try {
					response  = JSON.parse(response);
					if (response.cancel) {
						modal.find('.m-close').click();
						return;
					}
					callback(response);
				} catch(e) {
					frame.show();
					bindFrameUnload();

					if ($(this).contents().find('.form-ctrls .btn.draft[value="cancel"]').length > 0)
					{
						$(modal).on('modal:close', cancelOnClose);
					}

					var height = $(this).contents().find('body').height();
					$('.box', modal).height(height);
					$(this).height(height);
				}
			});

			frame.attr('src', url);
			bindFrameUnload();
		}
	};

	$.fn.FilePicker = function(defaults) {
		this.off('click');

		return this.each(function() {
			$(this).data('picker-initialized', true);

			$(this).on('click', function(){
				var options = {};

				// Duplicate the defaults object
				for (var property in defaults) {
					options[property] = defaults[property];
				}

				options.url = $(this).attr('href');
				options.rel = $(this).attr('rel');
				options.source = $(this);

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
					options.input_img = $(options.input_img);
				} else {
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
			// Someone already call .FilePicker() on this? Don't
			// bork their setup
			if ($(this).data('picker-initialized')) {
				return;
			}
			var modal = $("." + $(this).attr('rel')),
				options = {
					"source":      $(this),
					"input_value": $('input[name="' + $(this).data('input-value') + '"], textarea[name="' + $(this).data('input-value') + '"]'),
					"input_name":  $('#' + $(this).data('input-name')),
					"input_img":   $('#' + $(this).data('input-image')),
					"selected":    $(this).data('selected'),
					"url":         $(this).attr('href'),
					"rel":         $(this).attr('rel')
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
