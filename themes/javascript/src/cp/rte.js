(function($){
	
	var	$edit_toolset	= $('a[href*=edit_toolset]:not(.addTab)'),
		$modal			= $('<div id="rte_toolset_editor_modal"><div class="contents"/></div>');
	
	// make the modal
	$modal.dialog({
		width: 600,
		height: 430,
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
	function load_rte_builder( url )
	{
		$.get( url, function(data){
			$modal
				.find('.contents')
					.html( $( '#mainContent .contents', data ).html() )
					.find('div.heading')
						.remove()
						.end()
					.end()
				.dialog( 'option', 'title', $( '#mainContent .edit', data ).text() )
				.dialog('open');
		});
	}
	
	// Home Page - Trigger Toolset overlay
	if ( $edit_toolset.length )
	{
		$edit_toolset.click(function(e){
			e.preventDefault();
			var $a = $(this).closest('a');
			// Load the RTE Builder
			load_rte_builder( $a.attr('href') );
		});
	}
	
	// MyAccount - Trigger Toolset overlay
	var	$my_toolset_id		= $('#registerUser select#rte_toolset_id')
								.change(toggle_rte_edit_link),
		$edit_my_toolset	= $('<input type="button" class="submit"/>')
								.css('margin-left','5px')
								.val( EE.rte.edit_text );
	// Get the builder
	function get_rte_toolset_builder()
	{
		$modal.dialog('option', {height: 365});
		var
		builder_url	= EE.rte.toolset_builder_url.replace(/&amp;/g,'&'); // damn AMPs
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
			$my_toolset_id.parent().append( $edit_my_toolset );
			if ( $my_toolset_id.val() == 'new' )
			{
				get_rte_toolset_builder();
			}
		}
		else
		{
			$edit_my_toolset.remove();
		}
	}
	// Run once
	toggle_rte_edit_link();
	// Observe click
	$my_toolset_id
		.parent()
			.delegate('input.submit','click',get_rte_toolset_builder);
	
	
	// Toolset Builder
	var	$selected	= $('#null'),
		$used		= $selected,
		$unused		= $selected;
	
	function setupToolsetBuilder()
	{
		$error		= $('<div class="notice"/>').text( EE.rte.name_required );

		// Enable selection/de-selection
		$('body').on('click', '.rte-tool', function(e) {
			$(this).toggleClass('rte-tool-active');
		});
	
	    $("#rte-tools-selected, #rte-tools-unused").sortable({
			connectWith: '.rte-tools-connected',
			containment: '.rte-toolset-builder',
			revert: 200,
			tolerance:	'pointer',
			beforeStop: function(e, ui) {
				// Reaplce the destination item with the item(s) in our helper container
				$(ui.item).replaceWith(ui.helper.children().removeClass('rte-tool-active'));
			},
			helper: function(e, ui) {
				// jQuery UI doesn't (yet) provide a way to move multiple items, but
				// we can achieve it by wrapping selected items as the helper
				var $selected = $('.rte-tool-active');
	
				if ( ! $selected.length) {
					// shouldn't the below use ui.item? May be a UI bug.
					$selected = $(ui).addClass('rte-tool-active'); 
				}
	
				return $('<div/>').attr('id', 'draggingContainer').append($selected.clone());
		    },
			receive: function(e, ui) {
				$(ui.sender).parent().find('.rte-tool-active').addClass('rte-tool-remove');
			},
			start: function(e, ui) {
				// We don't want the placeholder to inherit this class
				$(this).children('.ui-sortable-placeholder').removeClass('rte-tool-active');
	
				// We use the helper during the drag operation, so hide the original
				// selected elements and 'mark' them for removal
				$(this).children('.rte-tool-active').hide().addClass('rte-tool-remove');
			},
			stop: function() {
				// Remove items that are marked for removal
				$('.rte-tool-remove').remove();
			}
		});

		
		// Ajax submission
		$('#rte-toolset-name')
			.parents('form')
				.submit(function( e ){
					$error.remove();
					e.preventDefault();
					
					update_rte_toolset();
					
					var	$this	= $(this),
						$name	= $('#rte-toolset-name'),
						value	= $name.val(),
						URL		= EE.rte.validate_toolset_name_url.replace(/&amp;/g,'&'),
						action	= $this.attr('action'),
					
					// figure out the toolset id (if we have one)
					toolset = action.replace(/.*?rte_toolset_id=(\d+)/,'$1');
					toolset = $.isNumeric( toolset ) ? '&rte_toolset_id=' + toolset : '';
					
					// validate the name
					$.get( URL + '&name=' + value + toolset, function(data) {
						if (value == '' || ! data.valid) {
							$error.appendTo($name.parent());
						}
						else {
							$.post(action + '&' + $this.serialize(), function(data) {
								$modal.dialog('close');
								window.location = window.location;
							});
						}
					},'json');
				 });
	}
	
	function update_rte_toolset()
	{
		var ids = [];

		$('#rte-tools-selected li').each(function() {
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