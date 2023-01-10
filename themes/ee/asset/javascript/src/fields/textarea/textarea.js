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
	$(document).ready(function () {
		EE.filePickerCallback = function(data, references) {
			var input = references.input_value;

			// May be a markItUp button
			if (input.length == 0) {
				input = references.source.parents('.markItUpContainer').find('textarea.markItUpEditor');
			}

			// Close the modal
			references.modal.find('.m-close').click();

			// Assign the value {filedir_#}filename.ext
			if (EE.fileManagerCompatibilityMode) {
				file_string = '{filedir_' + data.upload_location_id + '}' + data.file_name;
			} else {
				file_string = '{file:' + data.file_id + ':url}';
			}

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
		};

		// Need to make sure this is loaded after markItUp has added the image button :-/
		setTimeout(function() {
			$('.textarea-field-filepicker, li.html-upload').FilePicker({callback: EE.filePickerCallback});
		}, 1000);

		// Grid added a row? Hook up the new buttons!
		$('.tbl-wrap table').on('grid:addRow', function(event, el) {
			$(el).find('.grid-textarea').each(function() {
				var input_name = $(this).find('textarea').attr('name');
				$(this).find('.textarea-field-filepicker, li.html-upload').attr('data-input-value', input_name);
			});
			$(el).find('.textarea-field-filepicker, li.html-upload').FilePicker({callback: EE.filePickerCallback});
		});
	});
})(jQuery);
