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

	// Adding a tab
	$('.modal-add-new-tab .submit').on('click', function(e) {
		var tab_name = $('.modal-add-new-tab input[name="tab_name"]').val();
		var tab_id = 'custom__' + tab_name.replace(/ /g, "_").replace(/&/g, "and").toLowerCase();

		var legalChars = /^[^*>:+()\[\]=|"'.#$]+$/; // allow all unicode characters except for css selectors and $

		if (tab_name === "") {
			// Show the required_tab_name alert
			alert("The tab needs a name.");
		} else if ( ! legalChars.test(tab_name)) {
			// Show the illegal_tab_name alert
			alert("Illegal characters in that there tab name.");
		} else {
			var duplicate = false;
			for (var x = 0; x < EE.publish_layout.length; x++) {
				if (EE.publish_layout[x].id == tab_id) {
					duplicate = true;
				}
			}

			if (duplicate)
			{
				// Show the duplicate_tab_name alert
				alert("We cannot duplicate tab names")
			}
			else
			{
				var tab = {
					fields: [],
					id: tab_id,
					name: tab_name,
					visible: true
				};
				EE.publish_layout.push(tab);

				var index = $('div.tab-bar ul li').length;
				$('div.tab-bar ul').append('<li><a href="" rel="t-' + index + '">' + tab_name + '</a> <span class="tab-remove"></span></li>')
				$('div.tab.t-' + index - 1).after('<div class="tab t-' + index + '"></div>');

				$('.modal-add-new-tab input[name="tab_name"]').val('');
				$('.modal-add-new-tab .m-close').trigger('click');
			}
		}

		e.preventDefault();
	});

	// If you submit the form, trigger the submit button click
	$('.modal-add-new-tab form').on('submit', function(e) {
		$('.modal-add-new-tab .submit').trigger('click');
		e.preventDefault();
	});

	// Removing a tab
	$('div.tab-bar ul').on('click', '.tab-remove', function(e) {
		var tab = $(this).parents('li').eq(0);
		var index = $('div.tab-bar ul li').index(tab);
		var tabContents = $('div.tab.' + $(tab).find('a').eq(0).attr('rel'));

		if (tabContents.html())
		{
			alert("Cannot remove a tab with fields.");
			return;
		}

		EE.publish_layout.splice(index, 1);
		tab.remove();
		tabContents.remove();
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