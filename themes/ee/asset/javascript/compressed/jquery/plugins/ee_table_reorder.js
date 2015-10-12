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
$.fn.eeTableReorder=function(e){return this.each(function(){$("tbody",this).sortable({axis:"y",// Only allow vertical dragging
containment:"parent",// Contain to parent
handle:"td.reorder-col",// Set drag handle
cancel:"td.sort-cancel",// Do not allow sort on this handle
items:"tr",// Only allow these to be sortable
sort:EE.sortable_sort_helper,// Custom sort handler
forcePlaceholderSize:!0,// Custom sort handler
helper:function(e,t){var r=t.children(),o=t.clone();return o.children().each(function(e){
// Set helper cell sizes to match the original sizes
$(this).width(r.eq(e).width())}),o},
// Before sort starts
start:function(t,r){void 0!==e.beforeSort&&e.beforeSort(r.item)},
// After sort finishes
stop:function(t,r){void 0!==e.afterSort&&e.afterSort(r.item)}})})};