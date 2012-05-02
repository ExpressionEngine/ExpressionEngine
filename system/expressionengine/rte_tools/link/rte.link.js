/**
 * Links are hard
 */
WysiHat.addButton('link', {
	label:	EE.rte.link.add,
	
	init: function() {
		this.parent.init.apply(this, arguments);

		this.$link_dialog = this._setupDialog();
		this.$error = $('<div class="notice"/>').text(EE.rte.link.dialog.url_required);

		this.origState;
		this.link_node;

		return this;
	},

	handler: function(state, finalize)
	{
		this.origState = state;
		this.$editor.select();

		// reselect for FF
		this.Selection.set(state.selection);

		var sel		= window.getSelection(),
			link	= true,
			s_el, e_el;

		// get the elements
		s_el = sel.anchorNode;
		e_el = sel.focusNode;
		
		this.range = document.createRange();
		this.range.setStart(sel.anchorNode, sel.anchorOffset);
		this.range.setEnd(sel.focusNode, sel.focusOffset);

		if ((s_el == e_el && sel.anchorOffset == sel.focusOffset) ||
			e_el.textContent == '​') // Our zero-width character
		{
			link = false;
		}
		
		// If our initial check failed, but the selection still has
		// child nodes, a figure element may be selected and we need to
		// traverse down the nodes and see if an image tag is there;
		// or, we may have selected an image to begin with
		if (( ! link && s_el.childNodes.length > 0) ||
			$(s_el).is('img'))
		{
			while ( s_el.childNodes.length > 0 )
			{
				s_el = s_el.childNodes[0];
			}
			
			// If we found an image, and it's already in an anchor tag,
			// grab the anchor tag for selection instead
			if ($(s_el).is('img') &&
				$(s_el.parentNode).is('a'))
			{
				s_el = s_el.parentNode;
			}
			
			// If we ended up with an image or anchor tag, select it
			if ($(s_el).is('a') ||
				$(s_el).is('img'))
			{
				link = true;
				this.range.selectNode( s_el );
			}
		}
		
		// If our selected node is not an anchor tag or an image tag,
		// we may need to traverse our parents to see if we're already
		// in an anchor tag; if so, select it for editing
		if ( ! $(s_el).is('a') &&
			 ! $(s_el).is('img'))
		{
			// Reach the first element node
			while ( s_el.nodeType != 1 )
			{
				s_el = s_el.parentNode;
			}
			
			if ($(s_el).is('a'))
			{
				link = true;
				this.range.selectNode( s_el );
			}
		}
		
		this.link_node = s_el;

		if ( link )
		{
			this.$link_dialog.dialog('open');
			this.$link_dialog.bind('dialogclose', function() {
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

	query: function($editor)
	{
		return this.is('linked');
	},


	/////////////////////
	// Private Methods //
	/////////////////////


	_clearErrors: function()
	{
		this.$link_dialog.find('.notice').remove();
	},

	_editLinkNode: function(found, notfound)
	{
		var el = this.link_node;

		if (el)
		{
			while (el.nodeType != 1)
			{
				el = el.parentNode;
			}

			if (el.tagName.toLowerCase() == 'a')
			{
				found.call(this, $(el));
			}
			else if (notfound)
			{
				notfound.call(this);
			}
		}
	},

	_dialogOpen: function()
	{
		this._clearErrors();
		this._editLinkNode(
			function($el) {
				this.$url.val( $el.attr('href'));
				this.$title.val( $el.attr('title'));
				this.$external.prop('checked', $el.attr('target') == '_blank');
				this.$submit.val(EE.rte.link.dialog.update_link);
				$('#rte-remove-link').show();
			},
			function() {
				this.$submit.val(EE.rte.link.dialog.add_link);
				$('#rte-remove-link').hide();
			}
		);

		this.$url.focus();
	},

	_dialogClose: function()
	{
		var	title = $('#rte_link_title-').val();
		
		if (title != '')
		{
			this._editLinkNode(function($el) {
				$el.attr('title', title);
			});
		}

		// empty the fields
		this.$link_dialog.find('input[type=text],select').val('');
	},

	_keyEvent: function(e)
	{
		if (e.which == 13) // enter
		{
			this._validateLinkDialog();
		}
	},

	_removeLink: function()
	{
		this.Commands.deleteElement(this.link_node);

		this.$link_dialog.dialog('close');
		this.Selection.set(this.origState.selection);
	},

	_submit: function()
	{
		this._validateLinkDialog();
	},

	_setupDialog: function()
	{
		var that = this,
			$link_dialog = $(
			'<div id="rte-link-dialog">' +
			'<p><label>* ' + EE.rte.link.dialog.url_field_label + '</label>' +
			'<input type="text" name="url" required="required" /></p>' +
			'<p><label>' + EE.rte.link.dialog.title_field_label + '</label>' +
			'<input type="text" name="title" /></p>' +
			'<p><input type="checkbox" id="rte-link-dialog-external"/> ' +
			'<label for="rte-link-dialog-external">' + EE.rte.link.dialog.external_link + '</label></p>' +
			'<p class="buttons">' +
			'	<a id="rte-remove-link" style="display:none">' + EE.rte.link.dialog.remove_link + '</a>' +
			'	<input class="submit" type="submit" value="' + EE.rte.link.dialog.add_link +'" /></p>' +
			'</div>'
		);

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
				open: $.proxy(this, '_dialogOpen'),
				close: function() {
					setTimeout(function() {
						that._dialogOpen();
					}, 10);
				}
			})
			.on('keypress', 'input', $.proxy(this, '_keyEvent'))				// Close on Enter
			.on('click', '#rte-remove-link', $.proxy(this, '_removeLink'))		// Remove link
			.on('click', '#rte-link-dialog .submit', $.proxy(this, '_submit'));	// Add link

		this.$url		= $link_dialog.find('input[name=url]');
		this.$title		= $link_dialog.find('input[name=title]');
		this.$submit	= $link_dialog.find('input.submit');
		this.$external	= $link_dialog.find('#rte-link-dialog-external');

		return $link_dialog;
	},

	_validateLinkDialog: function()
	{
		this._clearErrors();
		
		var	url		= this.$url.val().replace(/^\s+|\s+$/g, ''),
			title	= this.$title.val();

		// is it empty?
		if (url == '')
		{
			this.$error.appendTo(this.$url.parent());
			return;
		}
		
		// Reselect the text/node
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(this.range);
		
		// Make a link! This is what the other 300
		// lines of code are here for, folks.
		this.make('link', url);
		
		// Select our new link so that Firefox will not keep the
		// selection inside the link, thus trapping the cursor, and
		// we also need to add the optional title attribute; if we
		// linked an image, the anchor is likely the focusNode
		var anchor_node = ($(sel.focusNode.parentNode).is('a'))
			? sel.focusNode.parentNode : sel.focusNode;
		
		// More fiddlyness to find the anchor node, this is also
		// mainly due to Firefox
		if ( ! $(anchor_node).is('a'))
		{
			if ($(this.link_node).is('a'))
			{
				anchor_node = this.link_node;
			}
			else if ($(this.link_node.parentNode).is('a'))
			{
				anchor_node = this.link_node.parentNode;
			}
		}
		
		if ($(anchor_node).is('a'))
		{
			this.range.selectNode(anchor_node);
			sel.addRange(this.range);
			
			// Title attribute
			if (title == '')
			{
				$(anchor_node).removeAttr('title');
			}
			else
			{
				$(anchor_node).attr('title', title);
			}

			// Target attribute
			if (this.$external.prop('checked'))
			{
				$(anchor_node).attr('target', '_blank');
			}
			else
			{
				$(anchor_node).removeAttr('target');
			}
		}
		
		// close
		this.$link_dialog.dialog('close');
	}
});