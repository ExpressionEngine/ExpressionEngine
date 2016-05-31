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
	this.$toolbar = null;

	this._add_buttons();
	this._bind_hover();
	this._disable_dragging();
	this._select_on_double_click();
}

Overlay.prototype = {

	/**
	 * Add buttons to the overlay.
	 */
	_add_buttons: function() {
		this.$toolbar = $('<ul/>', { class: 'toolbar', contenteditable: 'false' })
			.append(this._create_button('wrap_left', 'align-txt-left', 'in_text'))
			.append(this._create_button('align_left', 'align-left'))
			.append(this._create_button('align_center', 'txt-only', 'center'))
			.append(this._create_button('align_right', 'align-right'))
			.append(this._create_button('wrap_right', 'align-txt-right', 'in_text'));
	},

	/**
	 * Create a button given its name and hook up its actions.
	 */
	_create_button: function(action, className, extraText) {
		var that = this,
			listItem = $('<li/>', { class: className }),
			extraText = extraText || '',
			button = $('<a/>', { href: '#', title: this.lang[action] }).html(this.lang[extraText]);

		button.click(function(e) {
			e.preventDefault();
			that._button_actions()[action]();
			that._hide_toolbar();
		});

		return listItem.append(button);
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

			that.$current.append(that.$toolbar);

			that.$toolbar.data('image', $this);
		});

		this.$editor.on('mousemove', function(event) {
			if ($(event.toElement).closest('figure').size() == 0) {
				that._hide_toolbar();
			}
		});

		this.$editor.on('mouseleave', 'figure img', function(event) {
			if ($(event.toElement).closest('figure').size() == 0) {
				that._hide_toolbar();
			}
		});
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
		var $field = this.$toolbar.data('image'),
			that = this;

		if ( ! $field) {
			return
		}

		this.$figure.dblclick(function(e) {
			var	sel = window.getSelection(),
				range = document.createRange();

			e.preventDefault();
			range.selectNode($field.get(0));
			sel.removeAllRanges();
			sel.addRange(range);

			that._hide_toolbar();
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
	_hide_toolbar: function() {
		this.$toolbar.detach();
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
					that.$current
						.css('float', '')
						.data('floating', false);
				}
				that.$current.css('text-align', 'center');
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

function ImageChooser($editor, $image_button) {
	this.init($editor, $image_button);
}

ImageChooser.prototype = {

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

		this.$button.attr('rel', 'modal-file');
		this.$button.attr('href', EE.rte.image.url);
		this.$button.addClass('m-link');

		this.$button.FilePicker({
			callback: function(data, references) {
				// Close the modal
				references.modal.find('.m-close').click();

				setTimeout(function() {
					that._insert_image(data);
				}, 500);
			}
		});

		this.$button.click(function() {
			// make sure we have a ref to the file browser
			if ( ! that.$browser) {
				that.$browser = $('#file_browser');
			}

			// Keep the current range around until choosing a file
			// is completed in case the browser's selection changes
			// in the mean time
			that.$editor.focus();
			that.saved_ranges = that.Editor.Commands.getRanges();
		});
	},

	/**
	 * Insert the image into the editor after choosing / uploading
	 */
	_insert_image: function(image_object) {
		var $figure = $('<figure />'),
			caption_text = prompt(this.settings.caption_text, '');

		$figure.css('text-align', 'center').attr('class', 'rte-img-chosen');
		$figure.append(
			$('<img />', {
				alt: "",
				src: image_object.path
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
	}
};


WysiHat.addButton('image', {
	cssClass: 'rte-upload',
	title: EE.rte.image.title,
	label: EE.rte.image.add,
	init: function(name, $editor) {

		var filedirs	= EE.rte.image.filedirs,
			html		= $editor.html(),
			that		= this,
			path_re, path, imageChooser;

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
			imageChooser = new ImageChooser($editor, that.$element);
			new Overlay($editor);
		}, 50);

		return this.parent.init(name, $editor);
	},

	handler: function(state, finalize) {
		imageChooser.set_finalizer(finalize);
		return false;
	}
});

})();
