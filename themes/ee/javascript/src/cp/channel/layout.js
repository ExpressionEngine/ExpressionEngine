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

	var field_index_at_start = NaN;
	var tab_index_at_start = NaN;

	// Sorting the tabs
	$('div.tab-bar').sortable({
		cancel: "li:first-child",
		items: "li",
		start: function (event, ui)
		{
			tab_index_at_start = $('div.tab-bar ul li').index(ui.item[0]);
		},
		update: function (event, ui) {
			var index_at_stop = $('div.tab-bar ul li').index(ui.item[0]);

			var tab = EE.publish_layout.splice(tab_index_at_start, 1);
			EE.publish_layout.splice(index_at_stop, 0, tab[0]);

			tab_index_at_start = NaN;
		}
	});

	// Sorting the fields
	$('div.tab').sortable({
		connectWith: "div.tab",
		handle: "li.move a",
		items: "fieldset.sortable",
		start: function (event, ui)
		{
			field_index_at_start = $('div.tab-open fieldset').index(ui.item[0]);
			tab_index_at_start = getTabIndex();

			$('.tab-bar ul a').on('mouseover', function() {
				$(this).trigger('click');
			});
		},
		stop: function (event, ui) {
			if (ui.position == ui.originalPosition) {
				return;
			}

			var index_at_stop = $('div.tab-open fieldset').index(ui.item[0]);

			var field = EE.publish_layout[tab_index_at_start].fields.splice(field_index_at_start, 1);
			EE.publish_layout[getTabIndex()].fields.splice(index_at_stop, 0, field[0]);

			$('fieldset.sortable').removeClass('last');
			$('fieldset.sortable:last-child').addClass('last');

			field_index_at_start = NaN;
			$('.tab-bar ul a').off('mouseover');
		}
	});

	// Saving the on/off state of tabs
	$('.tab-on, .tab-off').on('click', function(e) {
		var tab = $(this).parents('li').eq(0);
		var index = $('div.tab-bar ul li').index(tab);
		var tabContents = $('div.tab.' + $(tab).find('a').eq(0).attr('rel'));

		if (tabContents.has('.required').length > 0)
		{
			alert("Cannot hide a tab with required fields.");
			return;
		}

		EE.publish_layout[index].visible = ! EE.publish_layout[index].visible;

		$(this).toggleClass('tab-on tab-off');

		e.preventDefault();
	});


	// Saving the hide/unhide state of fields
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

	$('form').on('submit', function(e) {
		$('input[name="field_layout"]').val(JSON.stringify(EE.publish_layout));
	});

});