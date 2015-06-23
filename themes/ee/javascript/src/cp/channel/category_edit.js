/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

"use strict";

(function ($) {
	$(document).ready(function () {

		var radios = $('input[name=cat_image_select]').parent(),
			input = $('input[name=cat_image]'),
			figure = input.parents('figure');

		if (input.attr('value') == '') {
			figure.hide();
		} else {
			radios.hide();
		}

		$('input[value=choose]')
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

		$('li.remove a').click(function (e) {
			var figure = $(this).parents('figure');
			figure.hide();
			figure.siblings('em').hide(); // Hide the "missing file" erorr
			figure.siblings('input[type="hidden"]').val('');
			figure.siblings('p.solo-btn').show();
			e.preventDefault();

			radios.show();
		});
	});
})(jQuery);
