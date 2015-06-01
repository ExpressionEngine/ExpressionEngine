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
		EE.file_picker_callback = function(data, references) {
			var input = references.input_value;
			console.log(data);

			// Close the modal
			references.modal.find('.m-close').click();

			// Assign the value {filedir_#}filename.ext
			input.insertAtCursor('<img src="{filedir_' + data.upload_directory + '}' + data.file_name + '" alt="" height="" width="">');
		};
	});
})(jQuery);
