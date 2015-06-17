/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
				var html = '<img src="{filedir_' + data.upload_location_id + '}' + data.file_name + '"';
				html = html + ' alt=""';

				if (data.file_hw_original) {
					dimensions = data.file_hw_original.split(' ');
					html = html + ' height="' + dimensions[0] + '" width="' + dimensions[1] + '"';
				}

				html = html + '>';

				input.insertAtCursor(html);
			}
		});
	});
})(jQuery);
