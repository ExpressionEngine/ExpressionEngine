/**
 * This is a rather complicated button, so it consists of three parts.
 *
 * First two helper objects to separate the two distinct parts of the
 * image workflow:
 *
 * Overlay - the overlay controls, align/float/remove
 * ImageChooser - hit the button, select/show the filebrowser
 *
 * And then lastly, at the end of the file, there is the button to hook
 * it all up.
 */
(function() {

function Overlay($editor) {

	this.lang = EE.rte.image;
	this.dragging = false;

	this.$current = null;
	this.$editor = $editor;
	this.$overlay = this._create_overlay();

	this._add_buttons();
	this._bind_hover();
	this._disable_dragging();
	this._select_on_double_click();
}

Overlay.prototype = {

	/**
	 * Create a blank overlay div and connect it to the editor. We'll fill
	 * in buttons later.
	 */
	_create_overlay: function() {
		var $overlay = $('<div id="rte_image_figure_overlay" class="WysiHat-ui-control"><p></p></div>');

		$overlay.hide()
		$overlay.appendTo(
			this.$editor.closest('.WysiHat-container')
		);

		return $overlay;
	},

	/**
	 * Add buttons to the overlay.
	 */
	_add_buttons: function() {
		this.$overlay.find('p')
			.append(this._create_button('align-left'))
			.append(this._create_button('align-center'))
			.append(this._create_button('align-right'))
			.append('<br/>')
			.append(this._create_button('wrap-left'))
			.append(this._create_button('wrap-none'))
			.append(this._create_button('wrap-right'))
			.append('<br/>')
			.append(this._create_button('remove'));
	},

	/**
	 * Create a button given its name and hook up its actions.
	 */
	_create_button: function(className) {
		var that = this,
			action = className.replace('-', '_');
			button = $('<button class="button '+className+'" type="button"><b>'+this.lang[action]+'</b></button>');

		button.attr('title', this.lang[action]);
		button.click(function(e) {
			e.preventDefault();
			that._button_actions()[action]();
			that._hide_overlay();
		});

		return button;
	},

	/**
	 * Show the overlay when hovering over an image
	 */
	_bind_hover: function() {
		var that = this;

		this.$editor.on('mouseover', 'figure img', function() {
			if (that.dragging) {
				return;
			}

			var	$this	= $(this),
				offsets = $this.position();

			that.$current = $this.closest('figure');
			that.$current.data('floating', (that.$current.css('float') != 'none'));

			that.$overlay.css({
				display:	'table',
				left:		offsets.left,
				top:		offsets.top,
				height:		$this.outerHeight(),
				width:		$this.outerWidth()
			});

			that.$overlay.data('image', $this);
		});

		this.$overlay.mouseleave($.proxy(this, '_hide_overlay'));
	},


	/**
	 * Don't show the overlay if the user is dragging / making a selection
	 */
	_disable_dragging: function() {
		this.$editor.on('mousedown', $.proxy(this, '_toggle_dragging', true));
		this.$editor.on('mouseup', $.proxy(this, '_toggle_dragging', false));
	},

	/**
	 * Double clicking on an image should select it. If we don't enforce this
	 * users who double click and then delete will have a markup mess on their
	 * hands.
	 */
	_select_on_double_click: function() {
		var $field = this.$overlay.data('image'),
			that = this;

		if ( ! $field) {
			return
		}

		this.$overlay.dblclick(function(e) {
			var	sel = window.getSelection(),
				range = document.createRange();

			e.preventDefault();
			range.selectNode($field.get(0));
			sel.removeAllRanges();
			sel.addRange(range);

			that._hide_overlay();
		});
	},

	/**
	 * Utility method to set the dragging flag
	 */
	_toggle_dragging: function(value) {
		this.dragging = value;
	},

	/**
	 * Hide the overlay
	 */
	_hide_overlay: function() {
		this.$overlay.hide();
		this.$current = null;
	},

	/**
	 * Align the <figure> tag currently being worked on.
	 */
	_align_figure: function(direction) {

		var css = { 'text-align': direction };

		if (this.$current.data('floating')) {
			css.float = direction;
		}

		this.$current.css(css);
	},

	/**
	 * A list of button names and their actions.
	 */
	_button_actions: function() {
		var that = this;

		return {
			align_left: $.proxy(that, '_align_figure', 'left'),
			align_right: $.proxy(that, '_align_figure', 'right'),

			align_center: function() {
				if (that.$current.data('floating')) {
					alert(that.lang.center_error);
				}
				else {
					that.$current.css('text-align', 'center');
				}
			},

			wrap_left: function() {
				that.$current
					.css('float', 'left')
					.data('floating', true);
			},

			wrap_right: function() {
				that.$current
					.css('float', 'right')
					.data('floating', true);
			},

			wrap_none: function() {
				that.$current
					.css('float', 'none')
					.data('floating', false);
			},

			remove: function() {
				that.$current.remove();
			}
		};
	}
};


var ImageChooser = {

	init: function($editor, $image_button) {

		this.settings = EE.rte.image;
		this.Editor = $editor.data('wysihat');
		this.saved_ranges = null;

		this.$browser = null;
		this.$editor = $editor;
		this.$button = $image_button;

		this.finalize = $.noop;

		this._add_hidden_field();
		this._bind_button();
		this._bind_form_submission();
	},

	/**
	 * Async buttons need to call their own finalizer method. Since we reuse
	 * the filebrowser across uploads, each button press will set a single use
	 * finalizer for us to use.
	 */
	set_finalizer: function(fn) {
		this.finalize = fn;
	},

	/**
	 * Create a hidden field ... @todo this was in here, what's it for?
	 */
	_add_hidden_field: function() {
		var $field = this.$editor.data('field'),
			$parent = this.$editor.parent();

		$parent.append('<input type="hidden" id="rte_image_' + $field.attr('name') + '"/>');
	},

	/**
	 * Bind the toolbar button to store the old ranges and trigger the filebrowser
	 * pop-up.
	 */
	_bind_button: function() {
		var that = this,
			$field = this.$editor.data('field');

		this.$button.click(function() {
			// make sure we have a ref to the file browser
			if ( ! that.$browser) {
				that.$browser = $('#file_browser');
			}

			// Keep the current range around until choosing a file
			// is completed in case the browser's selection changes
			// in the mean time
			that.saved_ranges = that.Editor.Commands.getRanges();
		});

		$.ee_filebrowser.add_trigger(
			this.$button,
			'userfile_' + $field.attr('name'),
			$.proxy(this, '_insert_image')
		);
	},

	/**
	 * Insert the image into the editor after choosing / uploading
	 */
	_insert_image: function(image_object) {
		var $figure = $('<figure />'),
			caption_text = prompt(this.settings.caption_text, '');

		$figure.css('text-align', 'center');
		$figure.append(
			$('<img />', {
				alt: "",
				src: image_object.thumb.replace(/_thumbs\//, '')
			})
		);

		if (caption_text) {
			$figure.find('img').attr('alt', caption_text);
			$figure.append(
				$('<figcaption/>').text(caption_text)
			);
		}

		this.$editor.focus();
		this.Editor.Commands.restoreRanges(this.saved_ranges);
		this.saved_ranges[0].insertNode( $figure.get(0) );

		this.$browser
			.find('#view_type').val('list').change() // switch the view back
			.parent().show();						 // show the footer again

		this.finalize();
		this.finalize = $.noop;
	},

	/**
	 * On form submission, convert file upload paths back to our
	 * {filedir_n} format for storage in the database.
	 */
	_bind_form_submission: function() {
		this.$editor.parents('form').submit($.proxy(this, '_replace_paths'));
	},

	/**
	 * Go through the field and replace all known paths with their
	 * {filedir_n} equivalents
	 */
	_replace_paths: function() {
		var folders = this.settings.folders;

		$('.WysiHat-field').each(function() {
			var value = this.value,
				path_re;

			for (path in folders) {
				path_re = new RegExp(path, 'g');
				value = value.replace(path_re, folders[path]);
			}

			this.value = value;
		});
	}
};


WysiHat.addButton('image', {
	label: EE.rte.image.add,
	init: function(name, $editor) {

		var filedirs	= EE.rte.image.filedirs,
			html		= $editor.html(),
			that		= this,
			path_re, path;

		// Firefox will return the left and right braces as entities,
		// we need to switch those back for replacement below
		html = html.replace('%7B', '{');
		html = html.replace('%7D', '}');

		for (path in filedirs) {
			path_re = new RegExp(path, 'g');
			html = html.replace( path_re, filedirs[path] );
		}

		$editor.html(html);

		// blargh
		setTimeout(function() {
			ImageChooser.init($editor, that.$element);
			new Overlay($editor);
		}, 50);

		return this.parent.init(name, $editor);
	},

	handler: function(state, finalize) {
		ImageChooser.set_finalizer(finalize);
		return false;
	}
});

})();
