(function(){
	
	var	$link_dialog	= $('<div class="rte-link-dialog">' +
							'<p><label>* ' + EE.rte.link.dialog.url_field_label + '</label>' +
							'<input type="url" required="required"/></p>' +
							'<p><label>' + EE.rte.link.dialog.title_field_label + '</label>' +
							'<input type="text"/></p>' +
							//'<p><label>' + EE.rte.link.dialog.rel_field_label + '</label>' +
							// '<select></select></p>' +
							'<p class="buttons">' +
							'	<a class="rte-link-remove js_hide">' + EE.rte.link.dialog.remove_link + '</a>' +
							'	<button class="submit" type="submit">' + EE.rte.link.dialog.add_link + '</button></p>' +
							'</div>'),
		$url			= $link_dialog.find('input[type=url]'),
		$title			= $link_dialog.find('input[type=text]'),
		//$rel			= $link_dialog.find('select'),
		uuid			= $editor.attr('id');

	// assign the UUIDs to make the fields and labels associate
	$url
		.attr('id','rte_link_url-'+uuid)
		.prev('label')
			.attr('for','rte_link_url-'+uuid);
	$title
		.attr('id','rte_link_title-'+uuid)
		.prev('label')
			.attr('for','rte_link_title-'+uuid);
	//		$rel
	//			.attr('id','rte_link_rel-'+uuid)
	//			.prev('label')
	//				.attr('for','rte_link_rel-'+uuid);

	function reSelect()
	{
		// the paste handler collects selection data as part of the field. Hooray!
		var	$field	= $editor.data('field'),
			o_range	= $field.data('saved-range'),
			sel		= window.getSelection(),
			range	= document.createRange();

		// if the DOM changes this will fail
		try {
			
			if (sel != '')
			{
				// If the selection isn't empty, but startOffset and endOffset
				// are the same, the user likely selected all text in the editor;
				// instead of claiming no text was selected to the user, we'll
				// edit the range so link-creation still works
				if (o_range.startOffset == o_range.endOffset)
				{
					o_range.startOffset = 0;
				}
			}
			
			// recreate the Range
			range.setStart( o_range.startContainer, o_range.startOffset );
			range.setEnd( o_range.endContainer, o_range.endOffset );

			// select the range
			sel.removeAllRanges();
			sel.addRange( range );
		} catch(e) {}

		return sel;
	}

	$link_dialog
		.appendTo('body')
		.dialog({
			width: 400,
			resizable: false,
			position: ["center","center"],
			modal: true,
			draggable: true,
			title: EE.rte.link.dialog.title,
			autoOpen: false,
			zIndex: 99999,
			open: function(e, ui) {
				// remove existing notices
				$link_dialog.find('.notice').remove();

				var	sel		= reSelect(),
					el		= sel.anchorNode;

				if ( el )
				{
					while ( el.nodeType != 1 )
					{
						el = el.parentNode;
					}

					$el = $(el);

					if ($el.is('a')) {
						$url.val( $el.attr('href'));
						$title.val( $el.attr('title'));
						$('.submit').text(EE.rte.link.dialog.update_link);
						$('.rte-link-remove').show();
					} else {
						$('.submit').text(EE.rte.link.dialog.add_link);
						$('.rte-link-remove').hide();
					}
				}
				
				$url.focus();
			},
			close: function(e, ui) {
				var	sel		= reSelect(),
					title	= $('#rte_link_title-'+uuid).val(),
					el		= sel.anchorNode;
				
				if ( el )
				{
					while ( el.nodeType != 1 )
					{
						el = el.parentNode;
					}
					el = $(el);
					if ( el.is('a') &&
					 	 title != '' )
					{
						el.attr('title',title);
					}
				}

				// empty the fields
				$link_dialog.find('input,select').val('');

				// trigger the update
				$editor.trigger( EE.rte.update_event );
			}
		})
		// Close on Enter
		.on('keypress', 'input', function(e){
			if (e.which == 13) {
				validateLinkDialog();
			}
		 })
		// Remove link
		.on('click', '.rte-link-remove', function(){
			$el.replaceWith($el.text());
	
			$link_dialog.dialog('close');
		})
		// Add link
		.on('click', '.submit', function(){
			validateLinkDialog();
		});


	function validateLinkDialog()
	{
		// remove existing notices
		$link_dialog.find('.notice').remove();

		// re-establish the selection
		reSelect();

		var	pass	= false,
			re_url	= /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
			$error	= $('<div class="notice"/>').text( EE.rte.link.dialog.url_required );

		if ( $url.val() != '' )
		{
			pass = re_url.test( $url.val() );
		}
		if ( pass )
		{
			$error.remove();

			// link!
			$editor.linkSelection( $url.val() );

			// close
			$link_dialog.dialog('close');
		}
		else
		{
			$error.appendTo( $url.parent() );
		}
	}

	toolbar.addButton({
		name:	'link',
	    label:	EE.rte.link.add,
	    handler: function(){
			var sel		= reSelect(),
				range	= document.createRange(),
				link	= true,
				s_el, e_el;

			// get the elements
			s_el = sel.anchorNode;
			e_el = sel.focusNode;
			

			if ((s_el == e_el &&
				sel.anchorOffset == sel.focusOffset) ||
				e_el.textContent == '​') // Our zero-width character
			{
				link = false;
			}

			// Can I get an A?
			while ( s_el.nodeType != 1 )
			{
				s_el = s_el.parentNode;
			}

			if ( s_el.nodeName.toLowerCase() == 'a' )
			{
				link = true;
				// select the whole <a>
				range.selectNode( s_el );
				$field.data(
					'saved-range',
					{
						startContainer:	range.startContainer,
						startOffset:	range.startOffset,
						endContainer: 	range.endContainer,
						endOffset:		range.endOffset
					}
				);
			}

			if ( link )
			{
				$link_dialog.dialog('open');
			}
			else
			{
				alert( EE.rte.link.dialog.selection_error );
			}
		},
		query: function( $editor ){
			return $editor.queryCommandState('createLink');
		}
	});
	
})();