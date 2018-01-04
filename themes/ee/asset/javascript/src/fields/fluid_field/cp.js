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
			var fluidField   = $(this).closest('.fluid-wrap'),
			    fieldToAdd   = $(this).data('field-name'),
			    fieldCount   = fluidField.data('field-count'),
			    fieldToClone = fluidField.find('.fluid-field-templates .fluid-item[data-field-name="' + fieldToAdd + '"]'),
			    fieldClone   = fieldToClone.clone();

			fieldCount++;

			fieldClone.html(
				fieldClone.html().replace(
					RegExp('new_field_[0-9]{1,}', 'g'),
					'new_field_' + fieldCount
				)
			);

			fluidField.data('field-count', fieldCount);

			// Enable inputs
			fieldClone.find(':input').removeAttr('disabled');

			// Insert it
			if ( ! $(this).parents('.fluid-item').length) {
				// the button at the bottom of the form was used.
				fluidField.find('.js-sorting-container').append(fieldClone);
			} else {
				$(this).closest('.fluid-item').after(fieldClone);
			}

			$.fuzzyFilter();

			// Bind the new field's inputs to AJAX form validation
			if (EE.cp && EE.cp.formValidation !== undefined) {
				EE.cp.formValidation.bindInputs(fieldClone);
			}

			e.preventDefault();
			fluidField.find('.open').trigger('click');

			FluidField.fireEvent($(fieldClone).data('field-type'), 'add', [fieldClone]);
	    };

		$('.fluid-wrap').on('click', 'a[data-field-name]', addField);

		$('.fluid-wrap').on('click', 'a.fluid-remove', function(e) {
			var el = $(this).closest('.fluid-item');
			FluidField.fireEvent($(el).data('field-type'), 'remove', el);
			el.remove();
			e.preventDefault();
		});

		$('.js-sorting-container').sortable({
			axis: 'y',						// Only allow horizontal dragging
			containment: 'parent',			// Contain to parent
			handle: 'span.reorder',			// Set drag handle to the top box
			items: '.fluid-item',			// Only allow these to be sortable
			sort: EE.sortable_sort_helper,	// Custom sort handler
			start: function (event, ui) {
				FluidField.fireEvent($(ui.item).data('field-type'), 'beforeSort', $(ui.item))
			},
			stop: function (event, ui) {
				FluidField.fireEvent($(ui.item).data('field-type'), 'afterSort', $(ui.item))
			}
		});

		// Remove the toggle event found in common.js
		$('.fieldset-faux-fluid .js-toggle-field').off('click');

		$('.fieldset-faux-fluid').on('click', '.js-toggle-field', function(){
			$(this)
				.parents('.fluid-item')
				.toggleClass('fluid-closed');
		});
	});
})(jQuery);
