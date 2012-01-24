var
$link_dialog = $(
					'<div id="rte_link_dialog">' +
						'<p><label for="rte_link_url">* ' + EE.rte.link_dialog.url_field_label + '</label>' +
						'<input type="url" id="rte_link_url" required="required"/></p>' +
						'<p><label for="rte_link_title">' + EE.rte.link_dialog.title_field_label + '</label>' +
						'<input type="text" id="rte_link_title"/></p>' +
						//'<p><label for="rte_link_rel">' + EE.rte.link_dialog.rel_field_label + '</label>' +
						// '<select id="rte_link_rel"></select></p>' +
						'<p class="buttons"><button class="submit" type="submit">' + EE.rte.link_dialog.submit_button +
						'</button></p>' +
					 '</div>'
				),
link_ranges	= [];

$link_dialog
	.appendTo('body')
	.dialog({
		width: 400,
		height: 170,
		resizable: false,
		position: ["center","center"],
		modal: true,
		draggable: true,
		title: EE.rte.link_dialog.title,
		autoOpen: false,
		zIndex: 99999,
		open: function(e, ui) {
			$editor.restoreRanges( link_ranges );
			
			// remove existing notices
			$('#rte_link_dialog .notice').remove();
			
			var
			selection	= window.getSelection(),
			el			= selection.anchorNode;
			if ( el )
			{
				while ( el.nodeType != 1 )
				{
					el = el.parentNode;
				}
				el = $(el);
				if ( el.is('a') )
				{
					$('#rte_link_url').val( el.attr('href') );
					$('#rte_link_title').val( el.attr('title') );
				}
			}
		},
		close: function(e, ui) {
			$editor.restoreRanges( link_ranges );
			
			var
			selection	= window.getSelection(),
			el			= selection.anchorNode,
			title		= $('#rte_link_title').val();
			if ( el )
			{
				while ( el.nodeType != 1 )
				{
					el = el.parentNode;
				}
				el = $(el);
				if ( el.is('a') )
				{
					el.attr('title',title);
				}
			}
			
			$('#rte_link_url,#rte_link_title').val('');
			
			// trigger the update
			$editor.trigger( EE.rte.update_event );
		}
	})
	// setup the close on enter
	.delegate('input','keypress',function( e ){
		// enter
		if ( e.which == 13 )
		{
			validateLinkDialog();
		}
	 })
	// setup the submit button
	.find('.submit')
		.click(validateLinkDialog);

function validateLinkDialog()
{
	// remove existing notices
	$('#rte_link_dialog .notice').remove();
	
	var
	pass	= false,
	$url	= $('#rte_link_url'),
	re_url	= /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i,
	$error	= $('<div class="notice"/>').text( EE.rte.link_dialog.url_required );
	if ( $('#rte_link_url') != '' )
	{
		pass = re_url.test( $url.val() );
	}
	if ( pass )
	{
		$error.remove();
		$editor.linkSelection( $url.val() );
		$link_dialog.dialog("close");
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

		link_ranges	= $editor.getRanges();

		var
		selection	= window.getSelection(),
		linkable	= !! selection.rangeCount,
		el			= selection.anchorNode,
		range		= document.createRange();
		
		if ( linkable &&
			 el == selection.focusNode &&
			 selection.anchorOffset == selection.focusOffset )
		{
			linkable = false;
		}

		while ( el.nodeType != 1 )
		{
			el = el.parentNode;
		}
		
		if ( el.nodeName.toLowerCase() == 'a' )
		{
			linkable = true;
			// select the whole <a>
			range.selectNode( el );
			selection.removeAllRanges();
			selection.addRange( range );
			link_ranges	= $editor.getRanges();
		}
		
		if ( linkable )
		{
			$link_dialog.dialog("open");
		}
		else
		{
			alert( EE.rte.link_dialog.selection_error );
		}
	},
	query: function( $editor ){
		return $editor.queryCommandState('createLink');
	}
});
