/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Plugin to handle sorting on our reorderable tables, provides
 * two callbacks for sorting events.
 *
 * Example:
 *
 * $('#mytable').eeTableReorder({
 *		beforeSort: function(row) {
 *			// Do something before sort
 *		},
 *		afterSort: function(row) {
 *			// Do something after sort
 *		}
 * });
 *
 * @param Object params An object literal containing key/value
 * pairs to provide optional settings.
 *
 * @option Function sortableContainer (optional) Selector for the container
 * containing the items that are sortable
 *
 * @option Function handle (optional) Selector for drag handle
 *
 * @option Function cancel (optional) Selector for drag handle when handle is
 * disabled
 *
 * @option Function item (optional) Selector for items that are draggable
 *
 * @option Function beforeSort (optional) Callback function to be
 * called before sorting starts, accepts table row object as
 * parameter.
 *
 * @option Function afterSort (optional) Callback function to be
 * called after sorting completes, accepts table row object as
 * parameter.
 *
 */
$.fn.eeTableReorder = function(params) {

	return this.each(function() {

		var that = this,
			defaults = {
				sortableContainer: 'tbody',
				handle: 'td.reorder-col',
				cancel: 'td.sort-cancel',
				item: '> tr'
			},
			config = {};

		config = $.extend(config, defaults, params);

		$(config.sortableContainer, this).sortable({
			axis: 'y',						// Only allow vertical dragging
			containment: 'parent',			// Contain to parent
			handle: config.handle,			// Set drag handle
			cancel: config.cancel,			// Do not allow sort on this handle
			items: config.item,				// Only allow these to be sortable
			sort: EE.sortable_sort_helper,	// Custom sort handler
			forcePlaceholderSize: true,		// Set an explict size on the placeholder
			helper: function(event, row)	// Fix issue where cell widths collapse on drag
			{
				var $originals = row.children();
				var $helper = row.clone();

				// Make sure radio buttons retain their state after sort,
				// explanation:
				// Upon finishing the sort, the new row will be put down
				// before the helper is destroyed, so for a brief moment
				// in time, there are multiple sets of radios with the
				// same name, and given the nature of radio buttons, only
				// one can be selected within the same name group, and the
				// helper wins; so, we'll just assign a random name to each
				$helper.find('input[type=radio]:enabled').each(function() {
					$(this).attr('name', Math.random() * 20);
				});

				$helper.children().each(function(index)
				{
					// Set helper cell sizes to match the original sizes
					$(this).width($originals.eq(index).width())
				});

				return $helper;
			},
			// Before sort starts
			start: function(event, ui)
			{
				if (params.beforeSort !== undefined)
				{
					params.beforeSort(ui.item);
				}
			},
			// After sort finishes
			stop: function(event, ui)
			{
				if (params.afterSort !== undefined)
				{
					params.afterSort(ui.item);
				}
			}
		});
	});
};
