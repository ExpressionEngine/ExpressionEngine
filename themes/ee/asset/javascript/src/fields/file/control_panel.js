/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

"use strict";

(function ($) {

	EE.FileField = {
		pickerCallback: function(data, references) {
			var input = references.input_value,
				figure = references.input_img.closest('figure'),
				name = references.input_img.closest('.fields-upload-chosen-file').next('.fields-upload-chosen-name');

			// Close the modal
			if (references.modal) {
				references.modal.find('.m-close').click();
			}

			// Assign the value {filedir_#}filename.ext
			input.val('{filedir_' + data.upload_location_id + '}' + data.file_name)
				.trigger('change')
				.trigger('hasFile', data);

			figure.toggleClass('no-img', ! data.isImage);
			figure.toggleClass('is-svg', data.isSVG);
			figure.find('img').toggleClass('hidden', ! data.isImage);

			if (data.isImage) {
				// Set the thumbnail
				references.input_img.attr('src', data.thumb_path);
			}

			// Fill in formatted caption
			name.html('<p><b>'+data.title+'</b></p>');

			// Show the image
			input.siblings('.fields-upload-chosen').removeClass('hidden');

			// Hide the upload button
			input.siblings('.fields-upload-btn').addClass('hidden');

			// Hide the "missing file" error
			input.siblings('em').remove();
		},

		setup: function(container) {
			$('.file-field-filepicker', container).FilePicker({
				callback: EE.FileField.pickerCallback
			});

			$('li.remove a', container).click(function (e) {
				var figure_container = $(this).closest('.fields-upload-chosen');
				figure_container.addClass('hidden');
				figure_container.siblings('em').remove(); // Hide the "missing file" erorr
				figure_container.siblings('input[type="hidden"]').val('').trigger('change');
				figure_container.siblings('.fields-upload-btn').removeClass('hidden');
				e.preventDefault();
			});

			// Drag and drop component
			FileField.renderFields(container)
		}
	}

	$(document).ready(function () {
		function sanitizeFileField(el) {
			var button = $('.file-field-filepicker', el),
				input = $('input[type="hidden"]', el),
				safe_name = input.attr('name').replace(/[\[\]']+/g, '_');

			button.attr('data-input-value', input.attr('name'));
			button.attr('data-input-image', safe_name);

			$('.fields-upload-chosen img', el).attr('id', safe_name);
		}

		EE.FileField.setup();

		FluidField.on('file', 'add', function(el) {
			sanitizeFileField(el);
			EE.FileField.setup(el);
		});

		Grid.bind('file', 'display', function(cell) {
			sanitizeFileField(cell);
			EE.FileField.setup(cell);
		});
	});
})(jQuery);
