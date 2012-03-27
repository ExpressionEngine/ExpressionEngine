(function($){
	var	$edit_toolset	= $('a[href*=edit_toolset]:not(.addTab)'),
		$modal			= $('<div id="rte_toolset_editor_modal"><div class="contents"/></div>');
	
	// make the modal
	$modal.dialog({
		width: 600,
		resizable: false,
		position: ["center","center"],
		modal: true,
		draggable: true,
		autoOpen: false,
		zIndex: 99999,
		open: function(e, ui) {
			setupToolsetBuilder();
		}
	});

	
	// Load the RTE Builder
	function load_rte_builder(url)
	{
		$.get(url, function(data){

			// Check to see what we're dealing with. The module returns the full
			// view, whereas the extension returns the innards.
			$contents = $('#mainContent .contents', data);
			
			if ($contents.size()) {
				modal_contents = $contents.html();
				modal_title = $('#mainContent .edit', data).text();
			} else {
				modal_contents = data;
				modal_title = EE.rte.toolset_modal.title;
			}

			$modal
				.find('.contents')
					.html(modal_contents)
					.find('div.heading')
						.remove()
						.end()
					.end()
				.dialog('option', 'title', modal_title)
				.dialog('open');
		});
	}

	// Home Page - Trigger Toolset overlay
	if ($edit_toolset.length )
	{
		$edit_toolset.click(function(e){
			e.preventDefault();
			var $a = $(this).closest('a');
			// Load the RTE Builder
			load_rte_builder( $a.attr('href') );
		});
	}
	
	// MyAccount - Trigger Toolset overlay
	var	$my_toolset_id		= $('#rte_toolset_id').change(toggle_rte_edit_link),
		$edit_my_toolset	= $('#edit_toolset');

	// Get the builder
	function get_rte_toolset_builder()
	{
		$modal.dialog();

		var builder_url	= EE.rte.toolset_builder_url.replace(/&amp;/g,'&'); // damn AMPs

		// My Custom Toolset - trigger edit on My Custom Toolset
		if ( $my_toolset_id.find('option:selected').text() == EE.rte.custom_toolset_text )
		{
			// pass the toolset id if we have it
			if ( $my_toolset_id.val() != 'new' )
			{
				builder_url += '&rte_toolset_id=' + $my_toolset_id.val();
			}
			// Load the RTE Builder
			load_rte_builder( builder_url );
		}
	}

	// add & remove the edit link
	function toggle_rte_edit_link()
	{
		if ( $my_toolset_id.find('option:selected').text() == EE.rte.custom_toolset_text )
		{
			$edit_my_toolset.show();

			if ( $my_toolset_id.val() == 'new' )
			{
				get_rte_toolset_builder();
			}
		}
		else
		{
			$edit_my_toolset.hide();
		}
	}

	// Run once
	$('#rte_toolset_id').change();

	// Observe click
	$my_toolset_id
		.parent()
			.delegate('input.submit','click',get_rte_toolset_builder);
	
	
	// Enable toolset item selection/de-selection
	$('#rte_toolset_editor_modal').on('click', '.rte-tool', function(e) {
		$(this).toggleClass('rte-tool-active');
	});

	// Toolset Builder
	function setupToolsetBuilder()
	{
		// Cancel link
		$('#rte-builder-closer').click(function(e) {
			e.preventDefault();
			$modal.dialog('close');
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
				
				// Remove placeholder fix element and re-add at end of both lists*
				$('.rte-placeholder-fix').remove();
				$('.rte-tools-connected').append('<li class="rte-placeholder-fix"/>');
			}
		});

		// *So, there's a frustratingly common edge case where the drag placeholder
		// appears *above* the last element in a list, but should appear *below* it
		// because your pointer is clearly at the end of the list. Forcing a dummy
		// li at the end of each list corrects this. Hacky, but... so is Droppable.
		$('.rte-tools-connected').append('<li class="rte-placeholder-fix"/>');


		// Ajax submission
		$('#rte_toolset_editor_modal form').submit(function(e) {
			e.preventDefault();
			
			update_rte_toolset();
			
			$.post($(this).attr('action'), $(this).serialize(), function(data) {
				if (data.error) {
					$('#rte_toolset_editor_modal .notice').text(data.error);

					return;
				}

				$.ee_notice(data.success, {type: 'success'});

				$modal.dialog('close');

				if (data.force_refresh) {
					window.location = window.location;
				}
			},'json');
		});
	}
	
	function update_rte_toolset()
	{
		var ids = [];

		$('#rte-tools-selected .rte-tool').each(function() {
			ids.push($(this).data('tool-id'));
		});

		// update the field
		$('#rte-toolset-tools').val( ids.join('|') );
	}
	
	// in the toolset builder page
	if ( $('#rte-tools-selected').length )
	{
		setupToolsetBuilder();
	}
	
})(jQuery);