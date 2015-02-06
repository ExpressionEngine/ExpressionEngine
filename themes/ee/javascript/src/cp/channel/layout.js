/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

$(document).ready(function () {

	function getTabIndex()
	{
		var tab = $('div.tab-bar a.act').parents('li').eq(0);
		return $('div.tab-bar ul li').index(tab);
	}

	function getFieldIndex(elemnet)
	{
		var field = $(elemnet).parents('fieldset').eq(0);
		return $('div.tab-open fieldset').index(field);
	}

	var index_at_start = NaN;

	// Sorting the tabs
	$('div.tab-bar').sortable({
		items: "li",
		start: function (event, ui)
		{
			index_at_start = $('div.tab-bar ul li').index(ui.item[0]);
		},
		stop: function (event, ui) {
			var index_at_stop = $('div.tab-bar ul li').index(ui.item[0]);

			var tab = EE.publish_layout.splice(index_at_start, 1);
			EE.publish_layout.splice(index_at_stop, 0, tab[0]);

			index_at_start = NaN
		}
	});

	// Sorting the fields
	$('form').sortable({
		handle: "li.move a",
		items: "fieldset.sortable",
		start: function (event, ui)
		{
			index_at_start = $('div.tab-open fieldset').index(ui.item[0]);
		},
		stop: function (event, ui) {
			var index_at_stop = $('div.tab-open fieldset').index(ui.item[0]);

			var field = EE.publish_layout[getTabIndex()].fields.splice(index_at_start, 1);
			EE.publish_layout[getTabIndex()].fields.splice(index_at_stop, 0, field[0]);

			$('fieldset.sortable').removeClass('last');
			$('fieldset.sortable:last-child').addClass('last');

			index_at_start = NaN
		}
	});

	// Saving the hide/unhide state
	$('li.hide a, li.unhide a').on('click', function(e) {
		var tab = getTabIndex();
		var field = getFieldIndex(this);

		EE.publish_layout[tab].fields[field].visible = ! EE.publish_layout[tab].fields[field].visible;

		$(this).parents('li').eq(0).toggleClass('hide unhide');

		e.preventDefault();
	});

	// Saving the collapsed state
	$('.sub-arrow').on('click', function(e) {
		var tab = getTabIndex();
		var field = getFieldIndex(this);

		EE.publish_layout[tab].fields[field].collapsed = ! EE.publish_layout[tab].fields[field].collapsed;

		e.preventDefault();
	});

});