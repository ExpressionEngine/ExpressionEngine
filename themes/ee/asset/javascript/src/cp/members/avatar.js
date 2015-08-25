/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

"use strict";

(function ($) {
	$(document).ready(function () {

		$('.avatarPicker').FilePicker({
			callback: function(data, picker) {
				picker.modal.find('.m-close').click();
				picker.input_value.val(data.file_id);
				picker.input_img.html("<img src='" + data.path + "' />");
				picker.input_img.parents('fieldset').show();
			}
		});

	});
})(jQuery);
