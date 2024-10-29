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
		// Disable inputs
		$('.fluid-field-templates :input').attr('disabled', 'disabled');

		// Disable inputs on submit too, so we don't send them if they showed up late
		$(".form-standard > form").on('submit', function(e) {
			$('.fluid-field-templates :input').attr('disabled', 'disabled');
		});

		$('.fluid').each(function() {
			var savedFluidItems = $(this).find('.js-sorting-container .fluid__item').length;
			$(this).attr('data-field-count', savedFluidItems);
		});

		var addField = function(e) {
			var fluidField   = $(this).closest('.fluid'),
				fieldToAdd   = $(this).data('field-name'),
				fieldCount   = fluidField.attr('data-field-count'),
				fieldToClone = fluidField.find('.fluid-field-templates .fluid__item[data-field-name="' + fieldToAdd + '"]'),
				fieldClone   = fieldToClone.clone();

			fieldCount++;

			fieldClone.html(
				fieldClone.html().replace(
					RegExp('new_field_[0-9]{1,}', 'g'),
					'new_field_' + fieldCount
				)
			);

			fluidField.attr('data-field-count', fieldCount);

			// Enable inputs
			fieldClone.find(':input').removeAttr('disabled');

			// Insert it
			if ( ! $(this).parents('.fluid__item').length) {
				// The main add button at the bottom was used
				fluidField.find('.js-sorting-container').append(fieldClone);
			}
			else {
				// The item's add button was used, so place it below itself
				$(this).closest('.fluid__item').after(fieldClone);
			}

			$.fuzzyFilter();

			// Bind the new field's inputs to AJAX form validation
			if (EE.cp && EE.cp.formValidation !== undefined) {
				EE.cp.formValidation.bindInputs(fieldClone);
			}

			e.preventDefault();
			// Hide the add item menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

            // If we cloned a field group fire 'add' events on all of its fields
            if ($(fieldClone).data('field-type') == 'field_group') {
                $(fieldClone).find('.fluid__item-field').each(function(index, element) {
                    FluidField.fireEvent($(element).data('field-type'), 'add', [$(element)]);
                });
            }

			FluidField.fireEvent($(fieldClone).data('field-type'), 'add', [fieldClone]);
			$(document).trigger('entry:preview');
	    };

		$('.fluid').on('click', 'a[data-field-name]', addField);

		$('.fluid').on('click', 'a.js-fluid-remove', function(e) {
			var el = $(this).closest('.fluid__item');
			var fluidCount = $(this).parents('.fluid').attr('data-field-count');

            // If we removed a field group fire 'remove' events on all of its fields
            if ($(el).data('field-type') == 'field_group') {
                $(el).find('.fluid__item-field').each(function (index, element) {
                    FluidField.fireEvent($(element).data('field-type'), 'remove', [element]);
                });
            }

			FluidField.fireEvent($(el).data('field-type'), 'remove', el);
			$(document).trigger('entry:preview');

			if (fluidCount > 0) {
				fluidCount--;
				el.parents('.fluid').attr('data-field-count', fluidCount);
			}

			el.remove();
			e.preventDefault();
		});

		// Toggle fluid item
		$('.fluid').on('click', '.js-toggle-fluid-item', function() {
			$(this).parents('.fluid__item').toggleClass('fluid__item--collapsed');

			// Hide the dropdown menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

			return false;
		});

		// Hide all fluid items
		$('.fluid').on('click', '.js-hide-all-fluid-items', function() {
			$(this).parents('.fluid').find('.js-sorting-container .fluid__item').addClass('fluid__item--collapsed');

			// Hide the dropdown menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

			return false;
		});

		// Show all fluid items
		$('.fluid').on('click', '.js-show-all-fluid-items', function() {
			$(this).parents('.fluid').find('.js-sorting-container .fluid__item').removeClass('fluid__item--collapsed');

			// Hide the dropdown menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

			return false;
		});

		// Make the fluid fields sortable
		$('.js-sorting-container').sortable({
			containment: false,
			handle: '.reorder', // Set drag handle to the top box
			items: '.fluid__item',			// Only allow these to be sortable
			sort: EE.sortable_sort_helper,	// Custom sort handler
			cancel: '.no-drag',
			appendTo: 'div.panel-body',
			start: function (event, ui) {
				$(ui.item).addClass('fluid__item--dragging')

				FluidField.fireEvent($(ui.item).data('field-type'), 'beforeSort', $(ui.item))
			},
			stop: function (event, ui) {
				$(ui.item).removeClass('fluid__item--dragging')

				FluidField.fireEvent($(ui.item).data('field-type'), 'afterSort', $(ui.item))

				$(document).trigger('entry:preview');
			}
		});
	});
})(jQuery);