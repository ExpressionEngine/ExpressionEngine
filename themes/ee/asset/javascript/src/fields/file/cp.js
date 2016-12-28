/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		function setupFileField(container) {
			$('.file-field-filepicker', container).FilePicker({
				callback: function(data, references) {
					var input = references.input_value,
						figure = references.input_img.closest('figure');

					// Close the modal
					references.modal.find('.m-close').click();

					// Assign the value {filedir_#}filename.ext
					input.val('{filedir_' + data.upload_location_id + '}' + data.file_name).trigger('change');

					figure.toggleClass('no-img', ! data.isImage);
					figure.find('img').toggleClass('hidden', ! data.isImage);

					if (data.isImage) {
						// Set the thumbnail
						references.input_img.attr('src', data.thumb_path);
					}

					var basename = data.title.substring(0, data.title.lastIndexOf('.')),
						extension = data.title.substring(data.title.lastIndexOf('.'), data.title.length);

					// Fill in formatted caption
					figure.find('figcaption').html('<b>'+basename+'</b>'+extension);

					// Show the image
					input.siblings('.fields-upload-chosen').removeClass('hidden');

					// Hide the upload button
					input.siblings('.fields-upload-btn').addClass('hidden');

					// Hide the "missing file" error
					input.siblings('em').remove();
				}
			});

			$('li.remove a').click(function (e) {
				var figure_container = $(this).closest('.fields-upload-chosen');
				figure_container.addClass('hidden');
				figure_container.siblings('em').remove(); // Hide the "missing file" erorr
				figure_container.siblings('input[type="hidden"]').val('').trigger('change');
				figure_container.siblings('.fields-upload-btn').removeClass('hidden');
				e.preventDefault();
			});
		}

		setupFileField();

		Grid.bind('file', 'display', function(cell) {
			var button = $('.file-field-filepicker', cell),
				input = $('input[type="hidden"]', cell),
				safe_name = input.attr('name').replace(/[\[\]']+/g, '_');

			button.attr('data-input-value', input.attr('name'));
			button.attr('data-input-image', safe_name);

			$('.fields-upload-chosen img', cell).attr('id', safe_name);

			setupFileField(cell);
		});
	});
})(jQuery);
