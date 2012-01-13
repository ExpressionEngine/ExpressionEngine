(function($){
	
	var
	$edit_toolset	= $('a[href*=edit_toolset]:not(.addTab)'),
	$modal			= $('<div id="rte_toolset_editor_modal"><div class="contents"/></div>');
	
	// make the modal
	$modal.dialog({
		width: 600,
		height: 400,
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
	
	// Home Page
	if ( $edit_toolset.length )
	{
		$edit_toolset.click(function(e){
			e.preventDefault();
			var $a = $(this).closest('a');
			$.get( $a.attr('href'), function(data){
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
			
		});
	}
	
	// MyAccount
	$('#registerUser select#rte_toolset_id').change(function(e){
		var
		$this		= $(this),
		builder_url	= EE.rte.toolset_builder_url.replace(/&amp;/g,'&'); // damn AMPs
		
		// My Custom Toolset
		if ( $this.find('option:selected').text() == EE.rte.custom_toolset_text )
		{
			// pass the toolset id if we have it
			if ( $this.val() != 'new' )
			{
				builder_url += '&rte_toolset_id=' + $this.val();
			}
			// get the builder and fill it in
			$.get( builder_url, function(data){
				$modal
					.find('.contents')
						.html( $( '#mainContent .contents', data ).html() )
						.end()
					.dialog('open');
			});
		}
	});
	
	
	// Toolset Builder
	var $selected	= $('#null'),
		$used		= $selected,
		$unused		= $selected;
	
	function setupToolsetBuilder()
	{
		$selected	= $('#null');
		$used		= $('#rte-tools-selected').bind( 'sortupdate', update_rte_toolset );
		$unused		= $('#rte-tools-unused');
		
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