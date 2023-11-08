/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

"use strict";

(function ($) {

	EE.FileField = {
		pickerCallback: function(data, references) {
			var input = references.input_value,
				figure = references.input_img.closest('figure'),
				name = references.input_img.closest('.list-item').children('.fields-upload-chosen-name');

			// Close the modal
			if (references.modal) {
				references.modal.find('.m-close').click();
			}

			// Assign the value {filedir_#}filename.ext
			if (EE.fileManagerCompatibilityMode) {
				input.val('{filedir_' + data.upload_location_id + '}' + data.file_name)
			} else {
				input.val('{file:' + data.file_id + ':url}');
				input.attr('data-id', data.file_id);
			}
			input.trigger('change')
				.trigger('hasFile', data);

			figure.toggleClass('no-img', ! data.isImage);
			figure.toggleClass('is-svg', data.isSVG);
			figure.find('img').toggleClass('hidden', ! data.isImage);

			if (! data.isImage && figure.find('i').length) {
				figure.find('i').remove();
			}

			if (data.isImage) {
				// Set the thumbnail
				references.input_img.attr('src', data.thumb_path);
				if (figure.find('i').length) {
					figure.find('i').remove();
				}
			} else if(data.file_type === 'archive') {
				references.input_img.after('<i class="fal fa-file-archive fa-3x"></i>');
			} else if(data.file_type === 'audio') {
				references.input_img.after('<i class="fal fa-file-audio fa-3x"></i>');
			} else if(data.file_type === 'video') {
				references.input_img.after('<i class="fal fa-file-video fa-3x"></i>');
			} else if (data.file_type === 'doc') {
				if (data.mime_type.includes('pdf')) {
					references.input_img.after('<i class="fal fa-file-pdf fa-3x"></i>');
				} else if (data.mime_type.includes('html') || data.mime_type.includes('css') || data.mime_type.includes('xml')) {
					references.input_img.after('<i class="fal fa-file-code fa-3x"></i>');
				} else if (data.mime_type.includes('rtf') || data.mime_type.includes('richtext') || data.mime_type.includes('word')) {
					references.input_img.after('<i class="fal fa-file-word fa-3x"></i>');
				} else if (data.mime_type.includes('excel') || data.mime_type.includes('spreadsheet') || data.mime_type.includes('csv')) {
					references.input_img.after('<i class="fal fa-file-spreadsheet fa-3x"></i>');
				} else if (data.mime_type.includes('powerpoint') || data.mime_type.includes('presentation')) {
					references.input_img.after('<i class="fal fa-file-powerpoint fa-3x"></i>');
				} else {
					references.input_img.after('<i class="fal fa-file-alt fa-3x"></i>');
				}
			} else {
				references.input_img.after('<i class="fas fa-file fa-3x"></i>');
			}

			// Fill in formatted caption
			name.html('<p><b>'+data.title+'</b></p>');
			name.attr('data-id', data.file_id);

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


			$('.button.remove', container).click(function (e) {
				var figure_container = $(this).closest('.fields-upload-chosen');

				figure_container.addClass('hidden');
				figure_container.siblings('em').remove(); // Hide the "missing file" erorr
				figure_container.siblings('input[type="hidden"]').val('').trigger('change');
				figure_container.siblings('.fields-upload-btn').removeClass('hidden');
				figure_container.find('.fields-upload-chosen-file i').remove();
				e.preventDefault();
			});

			$('.button.edit-meta', container).click(function (e) {
				e.preventDefault();
				var figure_container = $(this).closest('.fields-upload-chosen');
				var file_id = figure_container.siblings('input[type="hidden"]').attr('data-id');

				EE.cp.ModalForm.openForm({
					url: EE.file.publishCreateUrl.replace('###', file_id),
					load: (modal) => {
						if ($('div[data-select-react]', modal).length) {
							SelectField.renderFields();
						}

						$('.js-copy-url-button').on('click', function (e) {
							e.preventDefault();
							// copy asset link to clipboard
							var copyText = $(this).attr('href');

							document.addEventListener('copy', function(e) {
								e.clipboardData.setData('text/plain', copyText);
								e.preventDefault();
							}, true);

							document.execCommand('copy');

							// show notification
							$('.f_manager-alert').css('display', 'flex');
							DropdownController.hideAllDropdowns()

							// hide notification in 10 sec
							setTimeout(function() {
								$('.f_manager-alert').css('display', 'none');
							}, 5000);

							return false;
						});

					},
					success: (result) => {
						$('.fields-upload-chosen-name[data-id=' + file_id + ']').attr('title', result.title).text(result.title)
					}
				})
				e.stopImmediatePropagation();
				return false;
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
