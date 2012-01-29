(function($){
	
	var
	$edit_toolset	= $('a[href*=edit_toolset]:not(.addTab)'),
	$modal			= $('<div id="rte_toolset_editor_modal"><div class="contents"/></div>');
	
	// make the modal
	$modal.dialog({
		width: 600,
		height: 420,
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
	var
	$my_toolset_id		= $('#registerUser select#rte_toolset_id')
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
	var
	$selected	= $('#null'),
	$used		= $selected,
	$unused		= $selected;
	
	function setupToolsetBuilder()
	{
		$selected	= $('#null');
		$used		= $('#rte-tools-selected').bind( 'sortupdate', update_rte_toolset );
		$unused		= $('#rte-tools-unused'),
		$error		= $('<div class="notice"/>').text( EE.rte.name_required );
		
		// setup the sortables
		$used.add($unused)
			.sortable({
				connectWith:	'.rte-tools-connected',
				containment:	'.rte-toolset-builder',
				items:			'li:not(.rte-tool-placeholder)',
				opacity:		0.6,
				revert:			.25,
				tolerance:		'pointer'
			 });
		
		// tools behaviors
		$('li[data-tool-id]')
			.hover(
				function(){
					$(this).addClass('rte-tool-hover');
				},
				function(){
					$(this).removeClass('rte-tool-hover');
				}
			)
			.click(function(){
				$selected = $selected.add(
					$(this).addClass('rte-tool-active')
				);
			});

		// arrow behaviors
		$('#rte-tools-select').click(function(){
			$unused.find('li.rte-tool-active').appendTo($used);
			update_rte_toolset();
		});
		$('#rte-tools-deselect').click(function(){
			$used.find('li.rte-tool-active').appendTo($unused);
			update_rte_toolset();
		});
		
		// Ajax submission
		$('#rte-toolset-name')
			.parents('form')
				.submit(function( e ){
					$error.remove();
					e.preventDefault();
					
					var
					$this	= $(this),
					$name	= $('#rte-toolset-name'),
					value	= $name.val(),
					URL		= EE.rte.validate_toolset_name_url.replace(/&amp;/g,'&'),
					action	= $this.attr('action'),
					
					// figure out the toolset id (if we have one)
					toolset = action.replace(/.*?rte_toolset_id=(\d+)/,'$1');
					toolset = $.isNumeric( toolset ) ? '&rte_toolset_id=' + toolset : '';
					
					// validate the name
					$.get( URL + '&name=' + value + toolset, function( data ){
						if ( value == '' ||
						     ! data.valid )
						{
							$error.appendTo( $name.parent() );
						}
						else
						{
							$.post( action + '&' + $this.serialize(), function( data ){
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
		// grab the ids
		$used.find('li[data-tool-id]').each(function(){
			ids.push( $(this).data('tool-id') );
		});
		// update the field
		$('#rte-toolset-tools').val( ids.join('|') );
		// remove active classes
		$selected.removeClass('rte-tool-active');
		// make selected an empty jQuery set
		$selected = $('#noyourenevergonnagetit');
	}
	
	// in the toolset builder page
	if ( $('#rte-tools-selected').length )
	{
		setupToolsetBuilder();
	}
	
})(jQuery);