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
		$('.textarea-field-filepicker').FilePicker({
			callback: function(data, references) {
				var input = references.input_value;

				// Close the modal
				references.modal.find('.m-close').click();

				// Assign the value {filedir_#}filename.ext
				file_string = '{filedir_' + data.upload_location_id + '}' + data.file_name;

				// Output as image tag if image
				if (data.isImage) {
					var html = '<img src="' + file_string + '"';
					html = html + ' alt=""';

					if (data.file_hw_original) {
						dimensions = data.file_hw_original.split(' ');
						html = html + ' height="' + dimensions[0] + '" width="' + dimensions[1] + '"';
					}

					html = html + '>';

					input.insertAtCursor(html);
				} else {
					// Output link if non-image
					input.insertAtCursor('<a href="' + file_string + '">' + data.file_name + '</a>');
				}
			}
		});
	});
})(jQuery);
