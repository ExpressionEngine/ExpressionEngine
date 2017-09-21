/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

"use strict";

(function ($) {

	EE.cp.categoryEdit = {
		init: function(context) {
			var context = context || $('body'),
				radios = $('input[name=cat_image_select]', context).parent(),
				input = $('input[name=cat_image]', context),
				figure = input.parents('figure');

			if (input.attr('value') == '') {
				figure.hide();
			} else {
				radios.hide();
			}

			$('fieldset input[value=choose], fieldset li.edit a', context)
				.addClass('m-link')
				.attr('rel', 'modal-file')
				.attr('href', EE.category_edit.filepicker_url)
				.FilePicker({
					callback: function(data, references) {
						// Close the modal
						references.modal.find('.m-close').click();

						// Assign the value {filedir_#}filename.ext
						input.val('{filedir_' + data.upload_location_id + '}' + data.file_name);

						// Set the thumbnail
						$('img', figure).attr('src', data.path);

						// Show the figure
						input.parents('figure').show();

						// Hide the upload button
						radios.hide();

						// Hide the "missing file" error
						input.siblings('em').hide();
					}
				});

			$('li.remove a', context).click(function (e) {
				var figure = $(this).parents('figure');
				figure.hide();
				figure.siblings('em').hide(); // Hide the "missing file" erorr
				figure.find('input[type="hidden"]').val('');
				e.preventDefault();

				// Return radio selection back to none
				$('input[value=none]', context).click();

				radios.show();
			});
		}
	}
})(jQuery);
