/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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

		// Persist Fluid expand/collapse across Save when enabled via
		// Hidden Configuration Variable - fluid_field_persist_collapse => y/n
		var fluidPersistCollapse = (
			typeof EE !== 'undefined' &&
			EE.publish &&
			EE.publish.fluid_persist_collapse
		);
		var persistFluidCollapseState = function() {};

		if (fluidPersistCollapse) {
			var getEntryId = function() {
				if (typeof EE !== 'undefined' && EE.publish && EE.publish.entry_id) {
					return String(EE.publish.entry_id);
				}

				var href = window.location.href;
				var match = href.match(/publish\/edit\/entry\/(\d+)/);

				if (match) {
					return match[1];
				}

				var action = $('.form-standard > form').attr('action') || '';
				match = action.match(/publish\/edit\/entry\/(\d+)/);

				return match ? match[1] : null;
			};

			var getUserId = function() {
				if (typeof EE !== 'undefined' && EE.user_id) {
					return String(EE.user_id);
				}

				return 'anon';
			};

			var getFluidFieldKey = function($fluid) {
				var $fieldset = $fluid.closest('fieldset[id^="fieldset-"]');

				if ($fieldset.length) {
					return $fieldset.attr('id').replace(/^fieldset-/, '');
				}

				var name = $fluid.find('.js-sorting-container .fluid__item :input[name]').first().attr('name') || '';
				var match = name.match(/^([^\[\]]+)/);

				return match ? match[1] : 'fluid';
			};

			// Use descendant query: malformed field-instruction HTML can nest later
			// Fluid rows under an <em>, so direct-child selectors miss them.
			var getFluidItems = function($fluid) {
				return $fluid.find('.js-sorting-container .fluid__item').filter(function() {
					return ! $(this).closest('.fluid-field-templates').length;
				});
			};

			var getFluidItemId = function($item) {
				var name = $item.find(':input[name*="[fields]"]').first().attr('name') || '';
				var match = name.match(/\[(field_\d+|new_field_\d+)\]/);

				if (match) {
					return match[1];
				}

				// Best-effort fallback: position within this Fluid field (fragile on reorder)
				var $items = getFluidItems($item.closest('.fluid'));
				var index = $items.index($item);

				return index >= 0 ? 'index_' + index : null;
			};

			var getStorageKey = function($fluid) {
				var entryId = getEntryId();

				if ( ! entryId) {
					return null;
				}

				return [
					'ee:fluidCollapse',
					getUserId(),
					entryId,
					getFluidFieldKey($fluid)
				].join(':');
			};

			var readCollapsedIds = function($fluid) {
				var key = getStorageKey($fluid);

				if ( ! key || typeof localStorage === 'undefined') {
					return null;
				}

				try {
					var raw = localStorage.getItem(key);

					if ( ! raw) {
						return [];
					}

					var data = JSON.parse(raw);

					if ($.isArray(data)) {
						return data;
					}

					if (data && $.isArray(data.collapsed)) {
						return data.collapsed;
					}
				} catch (err) {}

				return [];
			};

			var writeCollapsedIds = function($fluid, collapsedIds) {
				var key = getStorageKey($fluid);

				if ( ! key || typeof localStorage === 'undefined') {
					return;
				}

				try {
					localStorage.setItem(key, JSON.stringify({
						collapsed: collapsedIds
					}));
				} catch (err) {}
			};

			persistFluidCollapseState = function($fluid) {
				if ( ! $fluid || ! $fluid.length) {
					return;
				}

				var collapsedIds = [];

				getFluidItems($fluid).each(function() {
					var $item = $(this);

					if ( ! $item.hasClass('fluid__item--collapsed')) {
						return;
					}

					var itemId = getFluidItemId($item);

					if (itemId) {
						collapsedIds.push(itemId);
					}
				});

				writeCollapsedIds($fluid, collapsedIds);
			};

			var restoreFluidCollapseState = function($fluid) {
				var collapsedIds = readCollapsedIds($fluid);

				if (collapsedIds === null || ! collapsedIds.length) {
					return;
				}

				var collapsedMap = {};

				$.each(collapsedIds, function(i, id) {
					collapsedMap[id] = true;
				});

				getFluidItems($fluid).each(function() {
					var $item = $(this);
					var itemId = getFluidItemId($item);

					if (itemId && collapsedMap[itemId]) {
						$item.addClass('fluid__item--collapsed');
					}
				});
			};

			$('.fluid').each(function() {
				restoreFluidCollapseState($(this));
			});
		}

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
			var $fluid = $(this).closest('.fluid');
			var fluidCount = $fluid.attr('data-field-count');

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
				$fluid.attr('data-field-count', fluidCount);
			}

			el.remove();
			persistFluidCollapseState($fluid);
			e.preventDefault();
		});

		// Toggle fluid item
		$('.fluid').on('click', '.js-toggle-fluid-item', function() {
			var $item = $(this).closest('.fluid__item');
			var $fluid = $(this).closest('.fluid');

			$item.toggleClass('fluid__item--collapsed');
			persistFluidCollapseState($fluid);

			// Hide the dropdown menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

			return false;
		});

		// Hide all fluid items
		$('.fluid').on('click', '.js-hide-all-fluid-items', function() {
			var $fluid = $(this).closest('.fluid');

			$fluid.find('.js-sorting-container .fluid__item').addClass('fluid__item--collapsed');
			persistFluidCollapseState($fluid);

			// Hide the dropdown menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

			return false;
		});

		// Show all fluid items
		$('.fluid').on('click', '.js-show-all-fluid-items', function() {
			var $fluid = $(this).closest('.fluid');

			$fluid.find('.js-sorting-container .fluid__item').removeClass('fluid__item--collapsed');
			persistFluidCollapseState($fluid);

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
