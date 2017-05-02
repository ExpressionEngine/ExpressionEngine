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
			    fieldClone  = $('.fluid-field-templates .fluid-item[data-field-name="' + fieldToAdd + '"]').clone();

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

			$(fluidBlock).trigger('fluidBlock:addField', fieldClone);
	    };

		$('a[data-field-name]').click(addField);

		$('a.fluid-remove').click(function(e) {
			$(this).closest('.fluid-item').remove();
			e.preventDefault();
		});

		$('.fluid-wrap').sortable({
			axis: 'y',						// Only allow horizontal dragging
			containment: 'parent',			// Contain to parent
			handle: 'span.reorder',			// Set drag handle to the top box
			items: '.fluid-item',			// Only allow these to be sortable
			sort: EE.sortable_sort_helper	// Custom sort handler
		})
	});
})(jQuery);
