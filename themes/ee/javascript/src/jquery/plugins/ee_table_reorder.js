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

		var that = this;

		$('tbody', this).sortable({
			axis: 'y',						// Only allow vertical dragging
			containment: 'parent',			// Contain to parent
			handle: 'td.reorder-col',		// Set drag handle
			cancel: 'td.sort-cancel',		// Do not allow sort on this handle
			items: 'tr',					// Only allow these to be sortable
			sort: EE.sortable_sort_helper,	// Custom sort handler
			helper: function(event, row)	// Fix issue where cell widths collapse on drag
			{
				var $originals = row.children();
				var $helper = row.clone();

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

				// Re-zebra-stripe the table
				if (EE.cp !== undefined)
				{
					EE.cp.zebra_tables(that);
				}
			}
		});
	});
};