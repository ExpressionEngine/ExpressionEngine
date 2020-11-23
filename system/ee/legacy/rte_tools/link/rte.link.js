/**
 * Links are hard
 */
WysiHat.addButton('link', {
	cssClass: 'rte-link',

	title:	EE.rte.link.title,

	label:	EE.rte.link.add,

	init: function() {
		this.parent.init.apply(this, arguments);

		this.$link_dialog;
		this.$error = $('<em class="ee-form-error-message"/>').text(EE.rte.link.modal.url_required);

		this.origState;
		this.link_node;

		return this;
	},

	handler: function(state, finalize) {
		this.link_node = null;

		this.origState = state;
		this.$editor.select();

		var sel		= window.getSelection(),
			link	= true,
			test_el, s_el, e_el;

		// get the elements
		s_el = sel.anchorNode;
		e_el = sel.focusNode;

		this.range = sel.getRangeAt(0);

		if ((s_el == e_el && sel.anchorOffset == sel.focusOffset) ||
			e_el.textContent == '​') // Our zero-width character
		{
			link = false;
		}

		// find link element
		test_el = this._findLinkableNode(s_el, 'img', sel.anchorOffset);
		if ( ! this._is(test_el, 'a')) {
			test_el = this._findLinkableNode(e_el, 'img', sel.focusOffset);
		}

		// found?
		if (test_el !== false) {
			s_el = test_el;
			link = true;
			this.range.selectNode(s_el);
			this.link_node = s_el;
		}

		if ( link ) {
			var that = this;
			this.$link_dialog = this._setupDialog();

			this.$link_dialog.on('modal:open', function() {
				setTimeout(function() {
					that._dialogOpen();
				}, 10)
			});

			this.$link_dialog.on('modal:close', function() {
				that._dialogClose();
				setTimeout(function() {
					finalize();
					that.$link_dialog.remove();
				}, 50);
			});

			this.$link_dialog.trigger('modal:open');

			return false;
		}

		// only return false if we go async
		alert( EE.rte.link.modal.selection_error );
	},

	query: function($editor) {
		return this.is('linked');
	},


	/////////////////////
	// Private Methods //
	/////////////////////

	_is: function(node, name) {
		return (node.tagName && node.tagName.toLowerCase() == name);
	},

	_findLinkableNode: function(el, childTagName, offset) {
		var _is = this._is,
			firefox_node = el.childNodes[offset];

		// can we go deeper? do it!
		if (el.childNodes.length > 0 || _is(el, childTagName)) {
			while(el.childNodes.length > 0) {
				el = el.childNodes[0];
			}

			// If we found the child, and it's already in an anchor tag,
			// grab the anchor tag for selection instead
			if (_is(el, childTagName) && _is(el.parentNode, 'a')) {
				return el.parentNode;
			}
		}

		// ended up with a child or link? good, select them
		if (_is(el, 'a') || _is(el, childTagName)) {
			return el;
		}

		// look up for luck
		if ( ! _is(el, 'a') && ! _is(el, childTagName)) {
			while (el.nodeType != 1) {
				el = el.parentNode;
			}

			if (_is(el, 'a') || _is(el, childTagName)) {
				return el;
			}
		}

		// Firefox gives is the parent node, with the anchor offset
		// being the index of the node in the parent node
		if (firefox_node !== undefined) {
			if (_is(firefox_node, 'a')) {
				return firefox_node;
			}
		}

		return false;
	},

	_clearErrors: function() {
		this.$link_dialog.find('.ee-form-error-message').remove();
		this.$link_dialog.find('.invalid').removeClass('invalid');
	},

	_editLinkNode: function(found, notfound) {
		var el = this.link_node;

		if (el) {
			while (el.nodeType != 1) {
				el = el.parentNode;
			}

			if (el.tagName.toLowerCase() == 'a') {
				found.call(this, $(el));
			}
			else if (notfound) {
				notfound.call(this);
			}
		}
	},

	_dialogOpen: function() {
		this._clearErrors();
		this._editLinkNode(
			function($el) {
				this.$url.val($el.attr('href'));
				this.$title.val($el.attr('title'));

				var val = ($el.attr('target') == '_blank') ? 'y' : 'n';
				this.$external.val([val]);
				// Trigger the click event on this element
				this.$link_dialog.find('input[value=' + val + ']').click();

				this.$submit.val(EE.rte.link.modal.update_link);
				$('#rte-remove-link').show();
			},
			function() {
				this.$submit.val(EE.rte.link.modal.add_link);
				$('#rte-remove-link').hide();
			}
		);

		this.$url.focus();
	},

	_dialogClose: function() {
		var	title = $('#rte_link_title-').val();

		if (title != '') {
			this._editLinkNode(function($el) {
				$el.attr('title', title);
			});
		}
	},

	_removeLink: function() {
		this.Commands.deleteElement(this.link_node);

		this.$link_dialog.trigger('modal:close');
		this.Selection.set(this.origState.selection);
	},

	_submit: function(e) {
		e.preventDefault();
		this._validateLinkDialog();
	},

	_setupDialog: function() {
		var $link_dialog = $(EE.rte.link.modal.html)
			.appendTo('body')
			.hide()
			.on('click', '#rte-remove-link', $.proxy(this, '_removeLink'))
			.find('form').on('submit', $.proxy(this, '_submit'));

		this.$url		= $link_dialog.find('input[name=url]');
		this.$title		= $link_dialog.find('input[name=title]');
		this.$submit	= $link_dialog.find('input[type=submit]');
		this.$external	= $link_dialog.find('input[name=external]');

		return $link_dialog;
	},

	_validateLinkDialog: function() {
		this._clearErrors();

		var	url		= this.$url.val().replace(/^\s+|\s+$/g, ''),
			title	= this.$title.val();

		// is it empty?
		if (url == '') {
			this.$error.appendTo(this.$url.parent());
			this.$url.parents('fieldset').addClass('invalid');
			return;
		}

		this.$editor.focus();

		// Reselect the range
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(this.range);

		if (this.link_node) {
			this.range.selectNode(this.link_node);
		}

		this.$link_dialog.trigger('modal:close');

		// Make a link! This is what the other 300
		// lines of code are here for, folks.
		this.make('link', url);

		// Select our new link so that Firefox will not keep the
		// selection inside the link, thus trapping the cursor, and
		// we also need to add the optional title attribute; if we
		// linked an image, the anchor is likely the focusNode so
		// we try that first. IE doesn't always play that way, so
		// we try a few others as well.

		var sel = window.getSelection(),
			_is = this._is,
			anchor_node = this._findLinkableNode(sel.anchorNode, 'img', sel.anchorOffset);

		if ( ! _is(anchor_node, 'a')) {
			anchor_node = this._findLinkableNode(sel.focusNode, 'img', sel.focusOffset);
		}

		if (anchor_node !== false) {
			sel.removeAllRanges();
			this.range.selectNode(anchor_node);
			sel.addRange(this.range);

			// Title attribute
			if (title == '') {
				$(anchor_node).removeAttr('title');
			}
			else
			{
				$(anchor_node).attr('title', title);
			}

			// Target attribute
			if (this.$external.filter(':checked').val() == 'y') {
				$(anchor_node).attr('target', '_blank');
			}
			else
			{
				$(anchor_node).removeAttr('target');
			}
		}

		// close
		this.$link_dialog.trigger('modal:close');
	}
});


$('body').on('modal:open', '.modal-wrap, .modal-form-wrap, .app-modal', function(e) {
	// set the heightIs variable
	// this allows the overlay to be scrolled
	var heightIs = $(document).height();

	// fade in the overlay
	$('.app-overlay')
		.removeClass('app-overlay--destruct')
		.removeClass('app-overlay---closed')
		.addClass('app-overlay---open')
		.css('height', heightIs);

	if (e.linkIs) {
		// strongly warn the actor of their potential future mistakes
		if(e.linkIs.indexOf('js-modal--destruct') !== -1){
			$('.app-overlay')
				.addClass('app-overlay--destruct');
		}

		// warn the actor of their potential future mistakes
		if(e.linkIs.indexOf('js-modal--warning') !== -1){
			$('.app-overlay')
				.addClass('app-overlay--warning');
		}
	}

	// reveal the modal
	if ($(this).hasClass('modal-wrap')) {
		$(this).fadeIn('slow');
	} else {
		$(this).removeClass('app-modal---closed')
			.addClass('app-modal---open');
	}

	// remove viewport scroll for --side
	if (e.linkIs) {
		if(e.linkIs.indexOf('js-modal-link--side') !== -1){
			$('body').css('overflow','hidden');
		}
	}

	if(e.modalIs == 'live-preview'){
		$('.live-preview')
			.removeClass('live-preview---closed')
			.addClass('live-preview---open');
	}

	// remember the scroll location on open
	$(this).data('scroll', $(document).scrollTop());

	// scroll up, if needed, but only do so after a significant
	// portion of the overlay is show so as not to disorient the user
	if ( ! $(this).is('.modal-form-wrap, .app-modal--side'))
	{
		setTimeout(function() {
			$(document).scrollTop(0);
		}, 100);
	} else {
		// Remove viewport scroll
		$('body').css('overflow','hidden');
	}
});

$(document).on('keydown', function(e) {
	if (e.keyCode === 27) {
		$('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');
	}
});

$('body').on('modal:close', '.modal-wrap, .modal-form-wrap, .app-modal', function(e) {
	var modal = $(this)

	if (modal.is(":visible")) {
		// fade out the overlay
		$('.overlay').fadeOut('slow');

		if (modal.hasClass('modal-wrap')) {
			modal.fadeOut('fast');
		} else {
			// disappear the app modal
			modal.addClass('app-modal---closed');
			setTimeout(function() {
				modal.removeClass('app-modal---open');
			}, 500);

			if (modal.hasClass('app-modal--live-preview')) {
				// disappear the preview
				$('.live-preview---open').addClass('live-preview---closed');
				setTimeout(function() {
					$('.live-preview---open').removeClass('live-preview---open');
				}, 500);
			}
		}

		// distract the actor
		$('.app-overlay---open').addClass('app-overlay---closed');
		setTimeout(function() {
			$('.app-overlay---open').removeClass('app-overlay---open')
				.removeClass('app-overlay--destruct')
				.removeClass('app-overlay--warning');
		}, 500);

		// replace the viewport scroll, if needed
		setTimeout(function() {
			$('body').css('overflow','');
		}, 200);

		if ( ! $(this).is('.modal-form-wrap, .app-modal'))
		{
			$(document).scrollTop($(this).data('scroll'));
		} else {
			// Remove viewport scroll
			$('body').css('overflow','hidden');
		}

		var button = $('.form-ctrls input.btn, .form-ctrls button.btn', this);
		button.removeClass('work');
		button.val(button.data('submit-text'));
	}
});

// listen for clicks on the element with a class of overlay
$('body').on('click', '.m-close, .js-modal-close', function(e) {
	$(this).closest('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');

	// stop THIS from reloading the source window
	e.preventDefault();
});

$('body').on('click', '.overlay, .app-overlay---open', function() {
	$('.modal-wrap, .modal-form-wrap, .app-modal').trigger('modal:close');
});