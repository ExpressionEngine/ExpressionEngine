/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 4.0.0
 * @filesource
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		// Disable inputs
		$('.fluid-field-templates :input').attr('disabled', 'disabled');

	    var addField = function(e) {
			var fluidBlock  = $(this).closest('.fluid-wrap'),
			    fieldToAdd  = $(this).data('field-name'),
			    rowCount    = fluidBlock.data('row-count')
			    fieldClone  = $('.fluid-item[data-field-name="' + fieldToAdd + '"]').clone();

			rowCount++;

			fieldClone.html(
				fieldClone.html().replace(
					RegExp('new_row_[0-9]{1,}', 'g'),
					'new_row_' + rowCount
				)
			);

			fluidBlock.data('row-count', rowCount);

			// Enable inputs
			fieldClone.find(':input').removeAttr('disabled');

			// Bind the "add" button
			fieldClone.find('a[data-field-name]').click(addField);

			// Insert it
			if ( ! $(this).parents('.fluid-item').length) {
				// the button at the bottom of the form was used.
				$('.fluid-actions', fluidBlock).before(fieldClone);
			} else {
				$(this).closest('.fluid-item').after(fieldClone);
			}

			e.preventDefault();
			fluidBlock.find('.open').trigger('click');
	    };

		$('a[data-field-name]').click(addField);
	});
})(jQuery);
