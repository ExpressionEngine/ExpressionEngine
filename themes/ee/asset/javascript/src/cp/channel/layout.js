/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	// remove debug - it has tabs and we don't want fields to end up in them
	// we'll add it back in after all the events are bound
	var debug = $('.ee-debugger').remove();

	// Cache the elements - these selectors shouldn't grab debug even if it's
	// somehow still there.  Doppelt gemoppelt hält besser.
	// This will also speed up the code - we don't want to keep asking the dom
	// for elements
	var tabs = $('form .tab-wrap .tab-bar__tabs');
	var sheets = $('form .tab-wrap div.tab');

	function getTabIndex()
	{
		var tab = tabs.find('.tab-bar__tab.active')//.eq(0);//.parents('li').eq(0);
		return tabs.find('.tab-bar__tab').index(tab);
	}

	function getFieldIndex(element)
	{
		var field = $(element).parents('.layout-item').eq(0);
		return $('div.tab-open .layout-item').index(field);
	}

	var field;

	// Sorting the tabs
	tabs.sortable({
		cancel: ".tab-bar__tab:first-child",
		items: ".tab-bar__tab",
		start: function (event, ui)
		{
			tab_index_at_start = tabs.find('.tab-bar__tab').index(ui.item[0]);
		},
		update: function (event, ui) {
			var index_at_stop = tabs.find('.tab-bar__tab').index(ui.item[0]);

			var tab = EE.publish_layout.splice(tab_index_at_start, 1);
			EE.publish_layout.splice(index_at_stop, 0, tab[0]);

			tab_index_at_start = NaN;
		}
	});

	var spring;
	var spring_delay = 500;

	function makeTabsDroppable()
	{
		tabs.find('.tab-bar__tab').droppable({
			accept: ".layout-item-wrapper .js-layout-item",
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
				$('<div class="js-layout-item"></div>').append(ui.draggable.html()).prependTo($('div.tab-open .layout-item-wrapper'));

				if ($(ui.draggable).has('.field-option-required')) {
					var tab = $(this).closest('.tab-bar__tab');
					if ($(tab).find('.tab-off').length > 0) {
						$(tab).find('.tab-off').trigger('click');
					}
				}

				// Add the field to the publish_layout array
				EE.publish_layout[getTabIndex()].fields.unshift(field);
				field = null;
			},
			over: function(e, ui) {
				tab = this;
				spring = setTimeout(function() {
					$(tab).trigger('click');
					sheets.sortable("refreshPositions");
				}, spring_delay);
			},
			out: function(e, ui) {
				clearTimeout(spring);
			},
			deactivate: function(e, ui) {
				clearTimeout(spring);
			}
		});
	}

	makeTabsDroppable();

	var sortable_options_for_sheets = {
		appendTo: "div.form-standard",
		connectWith: "div.tab",
		cursor: "move",
		forceHelperSize: true,
		forcePlaceholderSize: true,
		handle: ".layout-item .layout-item__handle",
		helper: "clone",
		items: ".layout-item-wrapper .js-layout-item",
		placeholder: "drag-placeholder",
		start: function (event, ui) {
			var fieldIndex = sheets.filter('.tab-open').find('.layout-item-wrapper .js-layout-item').index(ui.item[0]);
			field = EE.publish_layout[getTabIndex()].fields.splice(fieldIndex, 1)[0];
			ui.placeholder.append('<div class="none"></div>');
		},
		stop: function (event, ui) {
			if (ui.position == ui.originalPosition) {
				return;
			}

			if (field != null) {
				var fieldIndex = sheets.filter('.tab-open').find('.layout-item-wrapper .js-layout-item').index(ui.item[0]);

				EE.publish_layout[getTabIndex()].fields.splice(fieldIndex, 0, field);
				field = null;
			}
		}
	};

	// Sorting the fields
	sheets.sortable(sortable_options_for_sheets);

	// Saving the on/off state of tabs
	$('.tab-on, .tab-off').on('click', function(e) {
		var tab = $(this).parents('.tab-bar__tab').eq(0);
		var index = tabs.find('.tab-bar__tab').index(tab);
		var tabContents = sheets.filter('.' + $(tab).attr('rel'));

		if (EE.publish_layout[index].visible && tabContents.has('.field-option-required').length > 0) {
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

			if (duplicate) {
				// Show the duplicate_tab_name alert
				input.after($('<em></em>').append(input.data('duplicate')));
				input.parents('fieldset').addClass('invalid');
			} else {
				var tab = {
					id: tab_id,
					name: tab_name,
					visible: true,
					fields: []
				};
				EE.publish_layout.push(tab);

				var index = $('form .tab-wrap .tab-bar .tab-bar__tab').length;

				tabs.find('.tab-bar__tab').droppable("destroy");

				tabs.append('<a class="tab-bar__tab js-tab-button" href="" rel="t-' + index + '">' + tab_name + '<span class="tab-remove"></span></a>');
				sheets.filter('.t-' + (index - 1)).after('<div class="tab t-' + index + '"><div class="layout-item-wrapper"></div></div>');

				makeTabsDroppable();

				// Update tabs
				sheets = $('form .tab-wrap div.tab');
				sheets.eq(-1).sortable(sortable_options_for_sheets);

				$('.modal-add-new-tab .js-modal-close').trigger('click');
			}
		}

		e.preventDefault();
	});

	$('.modal-add-new-tab .js-modal-close').on('click', function(e) {
		$('.modal-add-new-tab input[name="tab_name"]').val('');
		$('.modal-add-new-tab .setting-field em').remove();
		$('.modal-add-new-tab input[name="tab_name"]').parents('fieldset').removeClass('invalid');
	});

	// If you submit the form, trigger the submit button click
	$('.modal-add-new-tab form').on('submit', function(e) {
		$('.modal-add-new-tab .submit').trigger('click');
		e.preventDefault();
	});

	// Removing a tab
	tabs.on('click', '.tab-remove', function(e) {
		var tab = $(this).parents('.tab-bar__tab').eq(0);
		var index = $('.tab-bar .tab-bar__tab').index(tab);
		var tabContents = sheets.filter('.' + $(tab).attr('rel'));

		if (tabContents.find('.layout-item-wrapper').html().trim()) {
			$('body').prepend(EE.alert.not_empty.replace('%s', tab.text()));
			return;
		}

		EE.publish_layout.splice(index, 1);
		tab.remove();
		tabContents.remove();
	});

	// Saving the hide/unhide state of fields
	$('[data-publish] form').on('click', '.field-option-hide input', function(e) {
		var tab = getTabIndex();
		var field = getFieldIndex(this);

		EE.publish_layout[tab].fields[field].visible = ! EE.publish_layout[tab].fields[field].visible;
	});

	// Saving the collapsed state
	$('[data-publish] form').on('click', '.field-option-collapse input', function(e) {
		var tab = getTabIndex();
		var field = getFieldIndex(this);

		EE.publish_layout[tab].fields[field].collapsed = ! EE.publish_layout[tab].fields[field].collapsed;
	});

	$('[data-publish] form').on('submit', function(e) {
		$('input[name="field_layout"]').val(JSON.stringify(EE.publish_layout));
	});

	// put debug back
	if ($('body .ee-main').length) {
		$('body .ee-main').append(debug);
	} else {
		$('body').append(debug);
	}
});
