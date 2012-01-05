(function($){
	
	// make the modal
	$modal = $('<div id="rte_toolset_editor_modal"><div class="contents"/></div>')
				.appendTo('body')
				.overlay({
					// only exit by clicking an action
					closeOnEsc: false,
					closeOnClick: false,

					top:	'center',
					fixed:	false, 
					close:	'#rte-builder-closer',

					// Mask to create modal look
					mask: {
						color: '#262626',
						loadSpeed: 200,
						opacity: 0.85
					},
					
					onLoad: function(){
						setupToolsetBuilder();
					}
				});
	
	// Home Page
	var $edit_toolset = $('a[href*=edit_toolset]:not(.addTab)'), $modal;
	if ( $edit_toolset.length )
	{
		$edit_toolset.click(function(e){
			e.preventDefault();
			var $a = $(this).closest('a');
			$.get( $a.attr('href'), function(data){
				$modal
					.find('.contents')
						.html( $( '#mainContent .contents', data ).html() )
						.end()
					.overlay()
						.load();
			});
			
		});
	}
	
	// MyAccount
	$('#registerUser select#rte_toolset_id').change(function(e){
		var
		$this		= $(this),
		builder_url	= rte_toolset_builder_url.replace(/&amp;/g,'&');
		if ( $this.find('option:selected').text() == rte_custom_toolset_text )
		{
			e.preventDefault();
			if ( $this.val() != 'new' )
			{
				builder_url += '&rte_toolset_id=' + $this.val();
			}
			console.log(builder_url);
			$.get( builder_url, function(data){
				console.log(data);
				$modal
					.find('.contents')
						.html( $( '#mainContent .contents', data ).html() )
						.end()
					.overlay()
						.load();
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
		$used.add($unused).sortable({
			connectWith:	'.rte-tools-connected',
			containment:	'.rte-toolset-builder',
			items:			'li:not(.rte-tool-placeholder)',
			opacity:		0.6,
			revert:			.25,
			tolerance:		'pointer'
		});
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
		$used.find('li[data-tool-id]').each(function(){
			ids.push( $(this).data('tool-id') );
		});
		$('#rte-toolset-tools').val( ids.join('|') );
		$selected.removeClass('rte-tool-active');
		$selected = $('#noyourenevergonnagetit');
	}
	
	// in the toolset builder page
	if ( $('#rte-tools-selected').length )
	{
		setupToolsetBuilder();
	}
})(jQuery);