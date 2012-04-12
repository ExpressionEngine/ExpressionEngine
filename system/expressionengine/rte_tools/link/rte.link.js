(function(){
	var $editor, final_url, anchorNode, range, selUtil;

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
		$el;

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

				var	el = anchorNode;

				if (el) {
					while (el.nodeType != 1) {
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
				var	title	= $('#rte_link_title-').val(),
					el		= anchorNode;
				
				if (el) {
					while (el.nodeType != 1) {
						el = el.parentNode;
					}

					$el = $(el);

					if ($el.is('a') && title != '') {
						$el.attr('title',title);
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
			var $el = $(anchorNode);
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

		var	url			= $url.val().trim(),
			$error		= $('<div class="notice"/>').text(EE.rte.link.dialog.url_required);

		// is it empty?
		if (url == '') {
			$error.appendTo($url.parent());
			return;
		}

		$error.remove();

		// link!
		final_url = url;
		
		selUtil.set( selUtil.get(range) );
		$editor.linkSelection(final_url);
		
		// close
		$link_dialog.dialog('close');
	}

	toolbar.addButton({
		name:	'link',
		label:	EE.rte.link.add,
 		handler: function($e, state, finalize) {
			$editor = $e.data('wysihat');
			$editor.select();
			
			selUtil = $e.data('selectionUtil');
			selUtil.set(state.selection);
			
			var sel		= window.getSelection(),
				link	= true,
				s_el, e_el;

			// get the elements
			s_el = sel.anchorNode;
			e_el = sel.focusNode;
			

			if ((s_el == e_el && sel.anchorOffset == sel.focusOffset) ||
				e_el.textContent == '​') // Our zero-width character
			{
				link = false;
			}
			
			range = document.createRange();
			range.setStart(sel.anchorNode, sel.anchorOffset);
			range.setEnd(sel.focusNode, sel.focusOffset);

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
			}

			anchorNode = s_el;
			
			if ( link )
			{
				$link_dialog.dialog('open');
				$link_dialog.bind('dialogclose', function()
				{
					setTimeout(function() {
						finalize();
					}, 50);
				});
			}
			else
			{
				alert( EE.rte.link.dialog.selection_error );
			}

			return false;
		},
		query: function( $editor ){
			return $editor.queryCommandState('createLink');
		}
	});
	
})();