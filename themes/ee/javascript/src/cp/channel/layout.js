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
		var tab = $('ul.tabs a.act').parents('li').eq(0);
		return $('ul.tabs li').index(tab);
	}

	function getFieldIndex(elemnet)
	{
		var field = $(elemnet).parents('fieldset').eq(0);
		return $('div.tab-open fieldset').index(field);
	}

	var field;

	// Sorting the tabs
	$('ul.tabs').sortable({
		cancel: "li:first-child",
		items: "li",
		start: function (event, ui)
		{
			tab_index_at_start = $('ul.tabs li').index(ui.item[0]);
		},
		update: function (event, ui) {
			var index_at_stop = $('ul.tabs li').index(ui.item[0]);

			var tab = EE.publish_layout.splice(tab_index_at_start, 1);
			EE.publish_layout.splice(index_at_stop, 0, tab[0]);

			tab_index_at_start = NaN;
		}
	});

	var spring;
	var spring_delay = 500;

	$('ul.tabs li a').droppable({
		accept: "fieldset.sortable",
		hoverClass: "highlight",
		tolerance: "pointer",
		drop: function(e, ui) {
			// Stop the Timeout
			clearTimeout(spring);

			// Open the tab
			$(this).trigger('click');

			// Remove the fieldset from the old tab
			ui.draggable.remove();

			// Add the fieldset to the new tab
			$('<fieldset class="col-group sortable"></fieldset>').append(ui.draggable.html()).prependTo($('div.tab-open'));

			// Add the field to the publish_layout array
			EE.publish_layout[getTabIndex()].fields.splice(0, 0, field);
			field = null;

			// Make sure the last element has the last class
			$('fieldset.sortable').removeClass('last');
			$('fieldset.sortable:last-child').addClass('last');
		},
		over: function(e, ui) {
			tab = this;
			spring = setTimeout(function() {
				$(tab).trigger('click');
				$('div.tab').sortable("refreshPositions");
			}, spring_delay);
		},
		out: function(e, ui) {
			clearTimeout(spring);
		},
		deactivate: function(e, ui) {
			clearTimeout(spring);
		}
	});

	// Sorting the fields
	$('div.tab').sortable({
		appendTo: "div.box.publish",
		connectWith: "div.tab",
		cursor: "move",
		forceHelperSize: true,
		forcePlaceholderSize: true,
		handle: "li.move a",
		helper: "clone",
		items: "fieldset.sortable",
		placeholder: "drag-placeholder",
		start: function (event, ui)
		{
			var fieldIndex = $('div.tab-open fieldset').index(ui.item[0]);
			field = EE.publish_layout[getTabIndex()].fields.splice(fieldIndex, 1)[0];
			ui.placeholder.append('<div class="none"></div>');
		},
		stop: function (event, ui) {
			if (ui.position == ui.originalPosition) {
				return;
			}

			var fieldIndex = $('div.tab-open fieldset').index(ui.item[0]);

			EE.publish_layout[getTabIndex()].fields.splice(fieldIndex, 0, field);
			field = null;

			$('fieldset.sortable').removeClass('last');
			$('fieldset.sortable:last-child').addClass('last');
		}
	});

	// Saving the on/off state of tabs
	$('.tab-on, .tab-off').on('click', function(e) {
		var tab = $(this).parents('li').eq(0);
		var index = $('ul.tabs li').index(tab);
		var tabContents = $('div.tab.' + $(tab).find('a').eq(0).attr('rel'));

		if (tabContents.has('.required').length > 0)
		{
			$('body').prepend(EE.alert.required.replace('%s', tab.text()));
			return;
		}

		EE.publish_layout[index].visible = ! EE.publish_layout[index].visible;

		$(this).toggleClass('tab-on tab-off');

		e.preventDefault();
	});

	// Adding a tab
	$('.modal-add-new-tab button').on('click', function(e) {
		var input = $('.modal-add-new-tab input[name="tab_name"]');
		var tab_name = $('.modal-add-new-tab input[name="tab_name"]').val();
		var tab_id = 'custom__' + tab_name.replace(/ /g, "_").replace(/&/g, "and").toLowerCase();

		var legalChars = /^[^*>:+()\[\]=|"'.#$]+$/; // allow all unicode characters except for css selectors and $

		$('.modal-add-new-tab .setting-field em').remove();
		input.parents('fieldset').removeClass('invalid');

		if (tab_name === "") {
			// Show the required_tab_name alert
			input.after($('<em></em>').append(input.data('required')));
			input.parents('fieldset').addClass('invalid');
		} else if ( ! legalChars.test(tab_name)) {
			// Show the illegal_tab_name alert
			input.after($('<em></em>').append(input.data('illegal')));
			input.parents('fieldset').addClass('invalid');
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
				input.after($('<em></em>').append(input.data('duplicate')));
				input.parents('fieldset').addClass('invalid');
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

				var index = $('ul.tabs li').length;
				$('ul.tabs').append('<li><a href="" rel="t-' + index + '">' + tab_name + '</a> <span class="tab-remove"></span></li>')
				$('div.tab.t-' + index - 1).after('<div class="tab t-' + index + '"></div>');

				$('.modal-add-new-tab .m-close').trigger('click');
			}
		}

		e.preventDefault();
	});

	$('.modal-add-new-tab .m-close').on('click', function(e) {
		$('.modal-add-new-tab input[name="tab_name"]').val('');
		$('.modal-add-new-tab .setting-field em').remove();
		input.parents('fieldset').removeClass('invalid');
	});

	// If you submit the form, trigger the submit button click
	$('.modal-add-new-tab form').on('submit', function(e) {
		$('.modal-add-new-tab .submit').trigger('click');
		e.preventDefault();
	});

	// Removing a tab
	$('ul.tabs').on('click', '.tab-remove', function(e) {
		var tab = $(this).parents('li').eq(0);
		var index = $('ul.tabs li').index(tab);
		var tabContents = $('div.tab.' + $(tab).find('a').eq(0).attr('rel'));

		if (tabContents.html())
		{
			$('body').prepend(EE.alert.not_empty.replace('%s', tab.text()));
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

	$('div.publish form').on('submit', function(e) {
		$('input[name="field_layout"]').val(JSON.stringify(EE.publish_layout));
	});

});