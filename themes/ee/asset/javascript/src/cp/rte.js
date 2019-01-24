/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

	var	$toolset_editor = $('<div id="rte_toolset_editor_modal"><div class="contents"/></div>');

	// make the modal
	$toolset_editor.dialog({
		width: 600,
		resizable: false,
		position: ["center","center"],
		modal: true,
		draggable: true,
		autoOpen: false,
		zIndex: 99999,
		open: function(e, ui) {
			setupToolsetEditor();
		}
	});


	// My Account - Toolset dropdown
	$('body').on('change', '#toolset_id', function() {

		var toolset_id = $(this).val();

		if (toolset_id == '0' || toolset_id == EE.rte.my_toolset_id) {
			$('#edit_toolset').show();

			// show the toolset editor if creating a custom toolset for the first time
			if (toolset_id == '0') {
				load_rte_builder(EE.rte.url.edit_my_toolset.replace(/&amp;/g,'&'));
			}

			return;
		}

		$('#edit_toolset').hide();
	});

	// My Account - Fire dropdown change event once on load
	$('#toolset_id').change();

	// My Account - Edit button (for My Toolset)
	$('#edit_toolset').click(function(){
		load_rte_builder(EE.rte.url.edit_my_toolset.replace(/&amp;/g,'&'), EE.rte.lang.edit_my_toolset)
	});


	// Load the RTE Builder
	function load_rte_builder(url, dialog_title)
	{
		$.getJSON(url, function(data) {

			if (data.error) {
				$.ee_notice(data.error, {type: 'error'});
				return;
			}

			// populate dialog innards
			$toolset_editor.find('.contents').html(data.success);

			// show dialog
			$toolset_editor
				.dialog('option', 'title', dialog_title)
				.dialog('open');
		});
	}

	// Module home page
	$('body').on('click', '.edit_toolset', function(e) {
		e.preventDefault();

		// Editing or Creating?
		var title = (this.id == 'create_toolset') ? EE.rte.lang.create_toolset : EE.rte.lang.edit_toolset;

		// Load the RTE Builder
		load_rte_builder($(this).attr('href'), title);
	});


	// Enable toolset item selection/de-selection
	$toolset_editor.on('click', '.rte-tool', function() {
		$(this).toggleClass('rte-tool-active');
	});

	// Toolset Editor Modal
	function setupToolsetEditor()
	{
		// Cancel link
		$('#rte-builder-closer').click(function(e) {
			e.preventDefault();
			$toolset_editor.dialog('close');
		});

		$("#rte-tools-selected, #rte-tools-unused").sortable({
			connectWith: '.rte-tools-connected',
			containment: '.rte-toolset-builder',
			placeholder: 'rte-tool-placeholder',
			revert: 200,
			tolerance:	'pointer',
			beforeStop: function(e, ui) {
				// Replace the destination item with the item(s) in our helper container
				ui.item.replaceWith(ui.helper.children().removeClass('rte-tool-active'));
			},
			helper: function(e, ui) {
				// Make sure only items in *this* ul are highlighted
				$('.rte-tools-connected').not($(this)).children().removeClass('rte-tool-active');

				// Then make sure the item being dragged is actually highlighted
				// Shouldn't this use ui.item? May be a bug.
				ui.addClass('rte-tool-active');

				// jQuery UI doesn't (yet) provide a way to move multiple items, but
				// we can achieve it by wrapping highlighted items as the helper
				var $selected = $('.rte-tool-active');

				if ( ! $selected.length) {
					// Shouldn't this use ui.item? May be a bug.
					$selected = ui.addClass('rte-tool-active');
				}

				return $('<div/>')
					.attr('id', 'rte-drag-helper')
					.css('opacity', 0.7)
					.width($(ui).outerWidth())  // match our li widths (including padding)
					.append($selected.clone());
			},
			start: function(e, ui) {
				// We use the helper during the drag operation, so hide the original
				// highlighted elements and 'mark' them for removal
				$(this).children('.rte-tool-active').hide().addClass('rte-tool-remove');

				// We don't want the placeholder to inherit this class
				$(this).children('.ui-sortable-placeholder').removeClass('rte-tool-active');
			},
			stop: function() {
				// Remove items that are marked for removal
				$('.rte-tool-remove').remove();

				// Remove placeholder fix element* and re-add at end of both lists
				$('.rte-placeholder-fix').remove();
				$('.rte-tools-connected').append('<li class="rte-placeholder-fix"/>');
			}
		});

		// *So, there's a frustratingly common edge case where the drag placeholder
		// appears *above* the last element in a list, but should appear *below* it
		// because your pointer is clearly at the end of the list. Forcing a dummy
		// li at the end of each list corrects this. Hacky, but... so is Droppable.
		$('.rte-tools-connected').append('<li class="rte-placeholder-fix"/>');


		// AJAX submit handler
		$toolset_editor.find('form').submit(function(e) {

			e.preventDefault();

			var tool_ids = [];

			$('#rte-tools-selected .rte-tool').each(function() {
				tool_ids.push($(this).data('tool-id'));
			});

			// populate field with selected tool ids
			$('#rte-toolset-tools').val(tool_ids.join('|'));

			$.post($(this).attr('action'), $(this).serialize(), function(data) {

				if (data.error) {
					$('#rte_toolset_editor_modal .notice').text(data.error);
					return;
				}

				$.ee_notice(data.success, {type: 'success'});

				$toolset_editor.dialog('close');

				if (data.force_refresh) {
					window.location = window.location;
				}
			},'json');
		});
	}

})(jQuery);
