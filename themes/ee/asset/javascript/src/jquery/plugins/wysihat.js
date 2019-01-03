/*!  WysiHat - WYSIWYG JavaScript framework, version 0.2.1
 *  (c) 2008-2010 Joshua Peek
 *  JQ-WysiHat - jQuery port of WysiHat to run on jQuery
 *  (c) 2010 Scott Williams & Aaron Gustafson
 *  EL-WysiHat - Extensive rewrite of JQ-WysiHat for ExpressionEngine
 *  (c) 2012 EllisLab Corp.
 *
 *  WysiHat is freely distributable under the terms of an MIT-style license.
 *--------------------------------------------------------------------------*/

(function(document, $, undefined) {

/**
 * This file is rather lengthy, so I've organized it into rough
 * sections. I suggest reading the documentation for each section
 * to get a general idea of where things happen. The list below
 * are headers (except for #1) so that you can search for them.
 *
 * Core Namespace
 * Editor Class
 * Element Manager
 * Change Events
 * Paste Handler
 * Key Helper
 * Event Class
 * Undo Class
 * Selection Utility
 * Editor Commands
 * Commands Mixin
 * Formatting Class
 * Blank Button
 * Toolbar Class
 * Defaults and jQuery Binding
 * Browser Compat Classes
 */

/**
 * WysiHat Namespace
 *
 * Tracks registered buttons and provides the basic setup function.
 * Usually the latter should be called through $.fn.wysihat instead.
 */
var WysiHat = window.WysiHat = {

	name:	'WysiHat',

	/**
	 * Add a button
	 *
	 * This does not mean it will be displayed,
	 * it only means that it will be a valid button
	 * option in $.fn.wysihat.
	 */
	addButton: function(name, config) {
		this._buttons[name] = config;
	},

	/**
	 * Attach WysiHat to a field
	 *
	 * This is what makes it all happen. Most of the
	 * time you will want to use the jQuery.fn version
	 * though:
	 * $(textarea).wysihat(options);
	 */
	attach: function(field, options) {
		return new WysiHat.Editor($(field), options);
	},

	/**
	 * Simple Prototypal Inheritance
	 *
	 * Acts a lot like ES5 object.create with the addition
	 * of a <parent> property on the child which contains
	 * proxied versions of the parent *methods*. Giving us easy
	 * extending if we want it (we do).
	 *
	 * @todo bad place for this, it looks like you can extend wysihat
	 */
	inherit: function(proto, props)
	{
		function F() {
			var k;
			// Proxy the parent methods to get .parent working
			this.parent = {};
			for (k in proto) {
				if (proto.hasOwnProperty(k)) {
					this.parent[k] = $.proxy(proto[k], this);
				}
			}
		}

		var prop, obj;

		F.prototype = proto;
		obj = new F();

		// No hasOwn check here. If you pass an object with
		// a prototype as props, then you're a JavascrHipster.
		for (prop in props) {
			obj[prop] = props[prop];
		}

		return obj;
	},

	/**
	 * Available buttons.
	 * Don't touch it, use addButton above.
	 */
	_buttons: []
};


/**
 * WysiHat.Editor
 *
 * The parent class of the editor. Instantiating it gets the whole
 * snafu going. Holds the textarea and editor objects as well as
 * all of the utility classes.
 */

WysiHat.Editor = function($field, options) {

	this.$field = $field.hide();
	this.$editor = this.create();

	$field.before(this.$editor);
	this.createWrapper();

	this.Element = WysiHat.Element;
	this.Commands = WysiHat.Commands;
	this.Formatting = WysiHat.Formatting;

	this.init(options);

}

WysiHat.Editor.prototype = {

	/**
	 * Special empty entity so that we always have
	 * paragraph tags to work with.
	 */
	_emptyChar: String.fromCharCode(8203),
	_empty: function () {
	    return '<p>'+this._emptyChar+'</p>';
	},

	isEmpty: function() {
		html = this.$editor.html();

		if ( html == '' ||
			 html == '\0' ||
			 html == '<br>' ||
			 html == '<br/>' ||
			 html == '<p></p>' ||
			 html == '<p><br></p>' ||
			 html == '<p>\0</p>' ||
			 html == this._empty() )
		{
			return true;
		}

		return false;
	},

	/**
	 * Create the main editor html
	 */
	create: function() {
		var that = this;
		return $('<div/>', {
			'class': WysiHat.name + '-editor',

			'data': {
				'wysihat': this,
				'field': this.$field
			},

			'role': 'application',
			'contentEditable': 'true',

			// Respect textarea's existing row count settings
			// This is a guess based on the row height, and differs slightly between Webkit and Mozilla browsers
			'height': (this.$field.height() > 0) ? this.$field.height() * 1.8 : this.$field.attr('rows') * 27,

			// Text direction
			'dir': this.$field.attr('dir'),

			'html': WysiHat.Formatting.getBrowserMarkupFrom(this.$field),

			'blur': function() {
				that.updateField();
				that.$field.trigger('blur');
			}

		});
	},

	/**
	 * Wrap everything up so that we can do things
	 * like the image overlay without crazy hacks.
	 */
	createWrapper: function()
	{
		var that = this;
		this.$field.add(this.$editor).wrapAll(
			$('<div/>', {
				'class': WysiHat.name + '-container',

				// keep sizes in sync
				'mouseup': function()
				{
					if (that.$field.is(':visible'))
					{
						that.$editor.outerHeight(that.$field.outerHeight());
					}
					else if (that.$editor.is(':visible'))
					{
						that.$field.outerHeight(that.$editor.outerHeight());
					}
				}
			})
		);
	},

	/**
	 * Setup all of the utility classes
	 */
	init: function(options) {
		var $ed = this.$editor,
			that = this;

		this.Undo = new WysiHat.Undo();
		this.Selection = new WysiHat.Selection($ed);
		this.Event = new WysiHat.Event(this);
		this.Toolbar = new WysiHat.Toolbar($ed, options.buttons);

		this.$field.change($.proxy(this, 'updateEditor'));

		// if, on submit or autosave, the editor is active, we
		// need to sync to the field before sending the data
		$ed.closest('form').on('submit entry:autosave', function() {
			// Instead of checking to see if the $editor is visible,
			// we check to see if the $field is NOT visible to account
			// cases where the editor may be hidden in a dynamic layout
			if ( ! that.$field.is(':visible'))
			{
				that.updateField();
			}
		});
	},

	/**
	 * Update the editor's textarea
	 *
	 * Syncs the editor and its field from the editor's content.
	 */
	updateField: function()
	{
		this.$field.val( WysiHat.Formatting.getApplicationMarkupFrom(this.$editor) );
	},

	/**
	 * Update the editor contents
	 *
	 * Syncs the editor and its field from the fields's content.
	 */
	updateEditor: function()
	{
		this.$editor.html( WysiHat.Formatting.getBrowserMarkupFrom(this.$field) );
		this.selectEmptyParagraph();
	},

	/**
	 * Select Empty Paragraph
	 *
	 * Makes sure we actually have a paragraph to put our cursor in
	 * when the editor is completely empty.
	 */
	selectEmptyParagraph: function()
	{
		var $el	= this.$editor,
			val = $el.html(),
			s = window.getSelection(),
			r;

		if (this.isEmpty())
		{
			$el.html(this._empty());

			r = document.createRange();
			s.removeAllRanges();
			r.selectNodeContents($el.find('p').get(0));

			// Get Firefox's cursor behaving naturally by clearing out the
			// zero-width character; if we run this for webkit too, then it
			// breaks Webkit's cursor behavior
			if ($.browser.mozilla)
			{
				$el.find('p').eq(0).html('');
			}

			s.addRange(r);
		}
	}
};

WysiHat.Editor.constructor = WysiHat.Editor;


/**
 * Element Manager
 *
 * Holds information about available elements and can be used to
 * check if an element is of a valid type.
 */

WysiHat.Element = (function(){

	// @todo add tr somewhere
	var
	roots			= [ 'blockquote', 'details', 'fieldset', 'figure', 'td' ],

	sections		= [ 'article', 'aside', 'header', 'footer', 'nav', 'section' ],

	containers		= [ 'blockquote', 'details', 'dl', 'ol', 'table', 'ul' ],

	subContainers	= [ 'dd', 'dt', 'li', 'summary', 'td', 'th' ],

	content			= [ 'address', 'caption', 'dd', 'div', 'dt', 'figcaption', 'figure', 'h1', 'h2', 'h3',
						'h4', 'h5', 'h6', 'hgroup', 'hr', 'p', 'pre', 'summary', 'small' ],

	media			= [ 'audio', 'canvas', 'embed', 'iframe', 'img', 'object', 'param', 'source', 'track', 'video' ],

	phrases			= [ 'a', 'abbr', 'b', 'br', 'cite', 'code', 'del', 'dfn', 'em', 'i', 'ins', 'kbd',
	 					'mark', 'span', 'q', 'samp', 's', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr' ],

	formatting		= [ 'b', 'code', 'del', 'em', 'i', 'ins', 'kbd', 'span', 's', 'strong', 'u', 'font' ],

	html4Blocks		= [ 'address', 'blockquote', 'div', 'dd', 'dt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'pre' ],

	forms			= [ 'button', 'datalist', 'fieldset', 'form', 'input', 'keygen', 'label',
						'legend', 'optgroup', 'option', 'output', 'select', 'textarea' ];

	function is( $el )
	{
		var
		i	= arguments.length,
		ret	= false;
		while (ret == false && i-- > 1)
		{
			ret	= $el.is( arguments[i].join(',') );
		}
		return ret;
	}

	return {
		isRoot: function( $el )
		{
			return is( $el, roots );
		},
		isSection: function( $el )
		{
			return is( $el, sections );
		},
		isContainer: function( $el )
		{
			return is( $el, containers );
		},
		isSubContainer: function( $el )
		{
			return is( $el, subContainers );
		},
		isBlock: function( $el )
		{
			return is( $el, roots, sections, containers, subContainers, content );
		},
		isHTML4Block: function( $el )
		{
			return is( $el, html4Blocks );
		},
		isContentElement: function( $el )
		{
			return is( $el, subContainers, content );
		},
		isMediaElement: function( $el )
		{
			return is( $el, media );
		},
		isPhraseElement: function( $el )
		{
			return is( $el, phrases );
		},
		isFormatter: function( $el )
		{
			return is( $el, formatting );
		},
		isFormComponent: function( $el )
		{
			return is( $el, forms );
		},

		getRoots: function()
		{
			return roots;
		},
		getSections: function( $el )
		{
			return sections;
		},
		getContainers: function()
		{
			return containers;
		},
		getSubContainers: function()
		{
			return subContainers;
		},
		getBlocks: function()
		{
			return roots.concat( sections, containers, subContainers, content );
		},
		getHTML4Blocks: function()
		{
			return html4Blocks;
		},
		getContentElements: function()
		{
			return subContainers.concat( content );
		},
		getMediaElements: function()
		{
			return media;
		},
		getPhraseElements: function()
		{
			return phrases;
		},
		getFormatters: function()
		{
			return formatting;
		},
		getFormComponents: function()
		{
			return forms;
		}
	};

})();


/**
 * Change Events
 *
 * Binds to various events to fire things such fieldChange and
 * editorChange. Currently also handles browser insertion for
 * empty events.
 *
 * Will probably be removed in favor of a real event system.
 */

$(document).ready(function(){

	var
	$doc = $(document),
	previousRange,
	selectionChangeHandler;

	if ( 'onselectionchange' in document &&
		 'selection' in document )
	{
		selectionChangeHandler = function()
		{
			var
			range	= document.selection.createRange(),
			element	= range.parentElement();
			$(element)
				.trigger('WysiHat-selection:change');
		}

 		$doc.on('selectionchange', selectionChangeHandler);
	}
	else
	{
		selectionChangeHandler = function()
		{
			var
			element		= document.activeElement,
			elementTagName = element.tagName.toLowerCase(),
			selection, range;

			if ( elementTagName == 'textarea' ||
				 elementTagName == 'input' )
			{
				previousRange = null;
			}
			else
			{
				selection = window.getSelection();
				if (selection.rangeCount < 1) { return };

				range = selection.getRangeAt(0);
				if ( range && range.equalRange(previousRange) ) { return; }

				previousRange	= range;
				element			= range.commonAncestorContainer;
				while (element.nodeType == Node.TEXT_NODE)
				{
					element = element.parentNode;
				}
			}

			$(element)
				.trigger( 'WysiHat-selection:change' );
		};

		$doc.mouseup( selectionChangeHandler );
		$doc.keyup( selectionChangeHandler );
	}
});


/**
 * Paste Handler
 *
 * A paste helper utility. How this works, is that browsers will
 * fire paste before actually inserting the text. So that we can
 * quickly create a new contentEditable object that is outside the
 * viewport. Focus it. And the text will go in there. That makes
 * it much easier for us to clean up.
 */

WysiHat.Paster = (function() {

	// helper element to do cleanup on
	var $paster = $('<div id="paster" contentEditable="true"/>').css({
		'width': '100%',
		'height': 10,
		'position': 'absolute',
		'left': -9999
	});

	var _pollTime = 50,
		_pollTimeout = 200;

	return {
		getHandler: function(Editor)
		{
			return function(state, finalize) {
				var ranges = Editor.Commands.getRanges(),
					startC = ranges[0].startContainer,
					waitTime = 0;

				$paster.html('').css('top', $(document).scrollTop());
				$paster.appendTo(document.body);
				$paster.focus();

				setTimeout(function handlePaste() {

					// slow browser? wait a little longer
					if ( ! $paster.html())
					{
						waitTime += _pollTime;

						if (waitTime < _pollTimeout)
						{
							setTimeout(handlePaste, _pollTime);
							return;
						}
					}

					var $parentBlock = $(startC).closest(WysiHat.Element.getBlocks().join(','));

					if ($parentBlock.length)
					{
						Editor.Formatting.cleanupPaste($paster, $parentBlock.get(0).tagName);
					}
					else
					{
						Editor.Formatting.cleanupPaste($paster);
					}

					Editor.$editor.focus();
					Editor.Commands.restoreRanges(ranges);

					// attempt to clear out the range, this is necessary if they
					// select and paste. The browsers will still report the old contents.
					if (ranges[0].deleteContents)
					{
						ranges[0].deleteContents();
					}
					else
					{
						Editor.Commands.insertHTML(''); // IE 8 can't do deleteContents
					}

					if (Editor.isEmpty())
					{
						// on an empty editor we want to completely replace
						// otherwise the first paragraph gets munged
						Editor.selectEmptyParagraph();
					}

					Editor.Commands.insertHTML($paster.html());

					// The final cleanup pass will inevitably lose the selection
					// as it removes garbage from the markup.
					var selection = Editor.Selection.get();

					// This is basically a final cleanup pass. I wanted to avoid
					// running these since they touch the whole editor and not just
					// the pasted bits, but these methods are great at removing
					// markup cruft. So here we are.
					Editor.updateField();
					Editor.updateEditor();

					Editor.Selection.set(selection);

					$paster = $paster.remove();

					finalize();

				}, _pollTime);

				return false;
			};
		}
	};
})();

/**
 * Key Helper
 *
 * Small utility that holds key values and common shortcuts.
 */

var
KEYS,
keyShortcuts,
EventHandlers;

KEYS = (function() {
	var keys = {
		3: "enter",
		8: "backspace",
		9: "tab",
		13: "enter",
		16: "shift",
		17: "ctrl",
		18: "alt",
		27: "esc",
		32: "space",
		37: "left",
		38: "up",
		39: "right",
		40: "down",
		46: "delete",
		91: "mod", 92: "mod", 93: "mod",

	// argh
		59: ";",
		186: ";",
		187: "=",
		188: ",",
		189: "-",
		190: ".",
		191: "/",
		192: "`",
		219: "[",
		220: "\\",
		221: "]",
		222: "'",
		63232: "up",
		63233: "down",
		63234: "left",
		63235: "right",
		63272: "delete"
	};

	// numbers
	for (var i = 0; i < 10; i++) {
		keys[i + 48] = String(i);
	}

	// letters
	for (var i = 65; i <= 90; i++) {
		keys[i] = String.fromCharCode(i);
	}

	return keys;
})();

keyShortcuts = (function() {

	// @todo @future would be cool if cmd+s triggered an autosave
	// @todo give addon folks a way to add to these?
	var
	ios = /AppleWebKit/.test(navigator.userAgent) && /Mobile\/\w+/.test(navigator.userAgent),
	mac = ios || /Mac/.test(navigator.platform),
	prefix = mac ? 'cmd' : 'ctrl';

	return {
		'cut': prefix + '-x',
		'copy': prefix + '-c',
		'paste': prefix + '-v',
		'undo': prefix + '-z',
		'redo': prefix + '-shift-z',

		// @todo move to tools?
		'bold': prefix + '-b',
		'italics': prefix + '-i',
		'underline': prefix + '-u'
	};
})();


/**
 * Event Class
 *
 * Big kahuna of event handlers. This deals with both public and
 * private events.
 *
 * Here's the basic intended functionality. It binds on all of the
 * browser events that will ever fire on the darned thing. Then,
 * it looks for actions like typing, pasting, button pushing and
 * records their before and after states for undoing.
 *
 * It also holds names of the buttons, so that a button action can
 * be triggered directly. Loosely coupling buttons and letting devs
 * play with triggering actions in different ways without completely
 * copying our buttons.
 */

WysiHat.Event = function(obj)
{
	this.Editor = obj;
	this.$editor = obj.$editor;
	this.eventHandlers = [];

	this.textStart = null;
	this.pasteStart = null;
	this.textDeleting = false; // typing backwards ;)

	// helper classes
	this.Undo = obj.Undo;
	this.Selection = obj.Selection;

	this._hijack_events();

	// special events
	this.add('paste', WysiHat.Paster.getHandler(obj));

};

WysiHat.Event.prototype = {

	/**
	 * Add a handler for editor events. These
	 * are things such as "bold" or "paste". Not
	 * browser events!
	 */
	add: function(action, func)
	{
		this.eventHandlers[action] = func;
	},

	/**
	 * Do we have a handler?
	 */
	has: function(action)
	{
		return (action in this.eventHandlers);
	},

	/**
	 * Run the event handler.
	 *
	 * @param action event name
	 * @param state current state
	 * @param finalize completion callback for asynchronous tools
	 */
	run: function(action, state, finalize) {
		var ret = this.eventHandlers[action](state, finalize);

		// false means you run finalize yourself
		// in all other cases, we run it. If it was
		// already run, no harm done.
		if (ret !== false)
		{
			finalize();
		}
	},

	/**
	 * Pass an event to its handler
	 *
	 * $editor.fire('bold')
	 */
	fire: function(action)
	{
		var that = this,
			beforeState,
			finalize;

		this._saveTextState(action);

		// special case - undo and redo
		if (action == 'undo' || action == 'redo')
		{
			var modified,
				check = (action == 'undo') ? 'hasUndo' : 'hasRedo';

			if (this.Undo[check]())
			{
				modified = this.Undo[action](this.$editor.html());

				this.$editor.html(modified[0]);
				this.Selection.set(modified[1]);
			}

			return;
		}

		if ( ! this.has(action))
		{
			// let it go ...
			return true;
		}

		// mark text change
		beforeState = this.getState();

		// setup a finalizer for the event.
		// make sure it can only be run once
		finalize = function() {
			if (this.hasRun)
			{
				return;
			}

			this.hasRun = true;

			that.textChange(beforeState);
			that._saveTextState(action);
			that.$editor.focus();
		};

		this.run(action, beforeState, $.proxy(finalize, finalize));
	},

	/**
	 * Mark a text change. Takes the
	 * objects from getState as before
	 * and after [optional] parameters.
	 *
	 * @return void
	 */
	textChange: function(before, after)
	{
		after = after || this.getState();

		// this.Editor.updateField();
		this.Editor.selectEmptyParagraph();

		this.Undo.push(
			before.html, after.html,
			before.selection, after.selection
		);

		this.$editor.closest('form').trigger("entry:startAutosave");
		$(document).trigger('entry:preview');
	},

	/**
	 * Check if a current event matches
	 * a key action we're looking for
	 *
	 * isKeyCombo('ctrl-x', evt)
	 * isKeyCombo('esc', evt)
	 *
	 * @return bool
	 */
	isKeyCombo: function(strName, e) {
		var modified = '',
			keyname = '',
			minus = strName.indexOf('-') > -1;

		// european altGr
		if (e.altGraphKey)
		{
			return false;
		}

		if (e.metaKey) modified += 'cmd-';
		if (e.altKey) modified += 'alt-';
		if (e.ctrlKey) modified += 'ctrl-';
		if (e.shiftKey) modified += 'shift-';

		if ( ! minus && strName.length > 1)
		{
			// just looking for a modifier
			return modified.replace(/-$/, '') == strName;
		}

		keyname = KEYS[e.keyCode];

		if ( ! keyname)
		{
			return false;
		}

		return strName.toLowerCase() == (modified + keyname).toLowerCase();
	},

	/**
	 * Check for named events.
	 *
	 * @todo list of named events
	 * @todo let plugins add to them
	 *
	 * isEvent('copy', evt)
	 */
	isEvent: function(name, evt)
	{
		var type = evt.type;

		// just asking for a type?
		if (type == name)
		{
			return true;
		}

		// key events can look up shortcuts
		// but if it's not a key event, we're done
		if (type.substr(0, 3) != 'key')
		{
			return false;
		}

		var keyCombo = keyShortcuts[name];

		if ( ! keyCombo) {
			return false;
		}

		return this.isKeyCombo(keyCombo, evt);
	},

	/**
	 * Get the editor's current state
	 */
	getState: function()
	{
		return {
			html: this.$editor.html(),
			selection: this.Selection.get()
		};
	},

	/**
	 * Save the state of the text they have
	 * typed so far.
	 */
	_saveTextState: function(name)
	{
		// some events shouldn't affect this
		// @todo figure out the whole list
		if (name == 'redo')
		{
			return;
		}

		if (this.textStart)
		{
			this.textChange(this.textStart);
			this.textStart = null;
		}
	},

	// ---------------------
	// INTERNAL EVENT SYSTEM
	// These functions handle dom events, they do _not_ get called
	// from a triggered event (but they may trigger events).
	// ---------------------

	/**
	 * Takes control of all editor events
	 *
	 * This is what makes it all work. If we fail here, we're
	 * most definitely up the creek. No kidding.
	 */
	_hijack_events: function()
	{
		var event_map = {

			// @todo remember one event type can still have the
			// effects / fire another (keyEvent can result in a
			// selectionChange), but initially the shortest shortest
			// route is best.

			// 'focusout change': $.proxy(this._blurEvent, this),
			'selectionchange focusin mousedown': $.proxy(this._rangeEvent, this),
			'keydown keyup keypress': $.proxy(this._keyEvent, this),
			'cut undo redo paste input contextmenu': $.proxy(this._menuEvent, this),
			'focus': $.proxy(this._focusEvent, this)
		//	'click doubleclick mousedown mouseup': $.proxy(this._mouseEvent, this)
		};

		this.$editor.on(event_map);
	},

	_focusEvent: function() {
		if (this.Editor.isEmpty())
		{
			// on an empty editor we want to completely replace
			// otherwise the first paragraph gets munged
			this.Editor.selectEmptyParagraph();
		}
	},

	/**
	 * Key Combo Events
	 *
	 * Looks for known named key combinations and fires the
	 * correct event.
	 */
	_keyComboEvent: function(evt)
	{
		var attempt = ['undo', 'redo', 'paste'],
			name;

		if (evt.type == 'keydown')
		{
			while(name = attempt.shift())
			{
				if (this.isEvent(name, evt))
				{
					if (name == 'paste')
					{
						this.fire(name);
						return true;
					}

					evt.preventDefault();
					this.fire(name);
					return false;
				}
			}
		}

		return true;
	},

	_triggerPreivew: _.debounce(function()
	{
		this.Editor.updateField();
		$(document).trigger('entry:preview');
	}, 225),

	/**
	 * Handle key events
	 *
	 * Order most definitely matters here - don't touch it!
	 */
	_keyEvent: function(evt)
	{
		// currently ignoring these remove it if you
		// need it, shouldn't break anything
		if (evt.type == 'keypress')
		{
			this._triggerPreivew();
			return true;
		}

		if (evt.ctrlKey || evt.altKey || evt.metaKey)
		{
			return this._keyComboEvent(evt);
		}

		if (evt.type == 'keydown')
		{
			if (KEYS[evt.keyCode] == 'backspace')
			{
				if (this.textDeleting == false)
				{
					this.textDeleting = true;
					this._saveTextState('backspace');
				}
			}
			else
			{
				if (this.textDeleting == true)
				{
					this.textDeleting = false;
					this._saveTextState('keypress');
				}
			}

			if (this.textStart == null)
			{
				this.textStart = this.getState();
			}
		}
		else if (evt.type == 'keyup')
		{
			switch (KEYS[evt.keyCode])
			{
				case 'up':
				case 'down':
				case 'left':
				case 'right':
					this._saveTextState('keyup');
			}
		}
	},

	/**
	 * Potential Range Changes
	 *
	 * @todo put a fast check for range change here instead of
	 * grabbing the range and doing the string length trick in the util
	 */
	_rangeEvent: function(evt)
	{
		this._saveTextState(evt.type);
	},

	/**
	 * Events that can be triggered in the user's context
	 * menu. This doesn't work too well, we may need a pair
	 * of buttons for undo and redo. (@todo)
	 */
	_menuEvent: function(evt)
	{
		var that = this;

		var attempt = ['undo', 'redo', 'paste'],
			name;

		while(name = attempt.shift())
		{
			if (this.isEvent(name, evt))
			{
				if (name != 'paste')
				{
					evt.preventDefault();
				}

				this.fire(name);
			}
		}
	}
};

WysiHat.Event.constructor = WysiHat.Event;


/**
 * Undo Class
 *
 * Implements a basic undo and redo stack.
 *
 * As you would expect it keeps track of changes as they are handed
 * to it. Usually this is in the form of two pieces of html repre-
 * senting the before and after state of the editor. It will do a
 * simple diff to reduce its memory footprint.
 *
 * Additionally, it keeps track of the selection at the event end
 * points to give a more natural undo experience (try it in your
 * text editor - it reselects).
 */

WysiHat.Undo = function()
{
	this.max_depth = 75;
	this.saved = [];
	this.index = 0;
}

WysiHat.Undo.prototype = {

	/**
	 * Add a change to the undo stack
	 *
	 * Takes a before and after string. These can be arrays as long
	 * as equal indexes match up.
	 */
	push: function(before, after, selBefore, selAfter)
	{
		var diff = [],
			that = this;

		if ($.isArray(before))
		{
			diff = $.map(before, function(item, i) {
				return that._diff(item, after[i]);
			});
		}
		else
		{
			diff = this._diff(before, after);
		}

		if (diff)
		{
			// remove any redos we might have
			if (this.index < this.saved.length)
			{
				this.saved = this.saved.slice(0, this.index);
				this.index = this.saved.length;
			}

			// max_depth check
			if (this.saved.length > this.max_depth) {
				this.saved = this.saved.slice(this.saved.length - this.max_depth);
				this.index = this.saved.length;
			}

			this.index++;
			this.saved.push({
				changes: diff,
				selection: [selBefore, selAfter]
			});
		}
	},

	/**
	 * Undo the current event stack item
	 *
	 * Takes a string to undo on and returns the new one
	 */
	undo: function(S)
	{
		this.index--;
		var delta = this.saved[this.index],
			diff = delta.changes,
			length = diff.length;

		for (var i = 0; i < length; i++) {
			change = diff[i];
			S = S.substring(0, change[0]) + change[1] + S.substring(change[0] + change[2].length);
		}

		return [S, delta.selection[0]];
	},

	/**
	 * Redo the current event stack item
	 *
	 * Takes a string to redo on and returns the new one
	 */
	redo: function(S)
	{
		var delta = this.saved[this.index],
			diff = delta.changes,
			length = diff.length;

		for (var i = length - 1; i >= 0; i--) {
			change = diff[i];
			S = S.substring(0, change[0]) + change[2] + S.substring(change[0] + change[1].length);
		}

		this.index++;
		return [S, delta.selection[1]];
	},

	/**
	 * Undo available?
	 */
	hasUndo: function()
	{
		return (this.index != 0);
	},

	/**
	 * Redo available?
	 */
	hasRedo: function()
	{
		return (this.index != this.saved.length);
	},

	/**
	 * Simple line diffing algo
	 *
	 * Pretty naive implementation, but enough to recognize
	 * identical ends and wrapping. The two most common cases.
	 *
	 * Returns and array of differences. A difference looks like
	 * this: [index, old, new]. Returns null if str1 and str2
	 * are identical.
	 */
	_diff: function(str1, str2)
	{
		var l1 = str1.length,
			l2 = str2.length,
			diffs = [],
			trim_before = 0,
			trim_after = 0,
			substr_i;

		// easiest case
		if (str1 == str2) {
			return null;
		}

		// trim identical stuff off the beginning
		while(trim_before < l1 && trim_before < l2)
		{
			if (str1[trim_before] != str2[trim_before])
			{
				break;
			}

			trim_before++;
		}

		// trim identical stuff off the beginning
		while(trim_after < l1 && trim_after < l2)
		{
			if (str1[l1 - trim_after - 1] != str2[l2 - trim_after - 1])
			{
				break;
			}

			trim_after++;
		}

		// It involved walking through the whole thing? We can ignore
		// it the code below will take care of finding the smallest difference.
		if (trim_before == Math.min(l1, l2))
		{
			trim_before = 0;
		}
		if (trim_after == Math.min(l1, l2))
		{
			trim_after = 0;
		}

		// We have something to trim. Do it and recalculate lengths.
		if (trim_before || trim_after)
		{
			str1 = str1.substring(trim_before, l1 - trim_after + 1);
			str2 = str2.substring(trim_before, l2 - trim_after + 1);

			l1 = str1.length;
			l2 = str2.length;
		}

		// common case - wrapping / unwrapping
		if (l1 !== l2)
		{
			// always check for shorter in longer
			if (l1 < l2)
			{
				substr_i = str2.indexOf(str1);
			}
			else
			{
				substr_i = str1.indexOf(str2);
			}

			if (substr_i > -1)
			{
				if (l1 < l2)
				{
					return [
						[trim_before, '', str2.substr(0, substr_i)], // wrapping before text
						[trim_before + l1, '', str2.substr(substr_i + l1)] // wrapping after
					];
				}
				else
				{
					return [
						[trim_before, str1.substr(0, substr_i), ''], // unwrap before
						[trim_before + substr_i + l2, str1.substr(substr_i + l2), ''] // unwrap after
					];
				}
			}
		}

		// if the strings are long we can probably diff more,
		// but this is pretty darned good
		return [[trim_before, str1, str2]];
	}
};

WysiHat.Undo.constructor = WysiHat.Undo;


/**
 * Selection Utility
 *
 * Abstracts out some of the more simple range manipulations.
 *
 * Working with ranges can be a PITA, especially for simple cursor
 * movements like those the undo class needs to do to recreate its
 * selections. To make this a little easier on everyone, we built a
 * small utility.
 *
 * Provides get(), set(), and toString().
 */

WysiHat.Selection = function($el)
{
	this.$editor = $el;
	this.top = this.$editor.get(0);
}

WysiHat.Selection.prototype = {

	_replace: new RegExp('[\r\n]', 'g'),

	/**
	 * Get current selection offsets based on
	 * the editors *text* (not html!).
	 *
	 * @return [startIndex, endIndex]
	 */
	get: function(range)
	{
		var s = window.getSelection(),
			r = document.createRange(),
			length, topOffset;

		if (range === undefined)
		{
			if ( ! s.rangeCount)
			{
				return [0, 0];
			}

			range = s.getRangeAt(0);
		}

		length = range.toString().replace(this._replace, '').length;

		r.setStart(this.top, 0);
		r.setEnd(range.startContainer, range.startOffset);

		topOffset = r.toString().replace(this._replace, '').length;

		return [topOffset, topOffset + length];
	},

	/**
	 * Create a selection or move the current one.
	 * Again, this is text! Omit end to move the cursor.
	 *
	 * @param startIndex, endIndex
	 * OR
	 * @param [startIndex, endIndex] // as returned by get
	 */
	set: function(start, end)
	{
		if ($.isArray(start))
		{
			end = start[1];
			start = start[0];
		}

		var s = window.getSelection(),
			r = document.createRange(),
			startOffset, endOffset;

		startOffset = this._getOffsetNode(this.top, start, true);
		r.setStart.apply(r, startOffset);

		// collapsed
		if (end === undefined || end == start)
		{
			end = start;
			r.collapse(true);
		}
		else
		{
			endOffset = this._getOffsetNode(this.top, end, false);
			r.setEnd.apply(r, endOffset);
		}

		s.removeAllRanges();
		s.addRange(r);
	},

	/**
	 * Get the contents of the current selection
	 */
	toString: function(range)
	{
		var s = window.getSelection();

		if (range === undefined)
		{
			range = s.getRangeAt(0);
		}

		return range.toString();
	},

	/**
	 * Given a node and and an offset, find the correct
	 * textnode and offset that we can create a range with.
	 *
	 * You probably don't want to touch this :).
	 */
	_getOffsetNode: function(startNode, offset, isStart)
	{
		var	curNode = startNode,
			curNodeLen = 0,
			last = this.$editor.get(0).lastChild,
			blocks = WysiHat.Element.getBlocks();

		function getTextNodes(node)
		{
			if (node.nodeType == Node.TEXT_NODE || node.nodeType == Node.CDATA_SECTION_NODE)
			{
				if (offset > 0)
				{
					curNode = node;
					offset -= node.nodeValue.replace(/\n/g, '').length;
				}
			}
			else
			{
				for (var i = 0, len = node.childNodes.length; offset > 0 && i < len; ++i)
				{
					getTextNodes(node.childNodes[i]);
				}
			}
		}

		getTextNodes(startNode);

		if (offset == 0)
		{
			// weird case where they try to select a non text node
			// e.g. The beginning of the editor.
			if (curNode.nodeType != Node.TEXT_NODE)
			{
				// do our best to get to a text node
				while (curNode.firstChild !== null)
				{
					curNode = curNode.firstChild;
				}

				return [curNode, 0];
			}

			// Offset 0 means we're at the end of the node.
			// If we're starting a selection and the end is a
			// block node, we need to jump to the next one so
			// that we don't select that initial newline.
			if (isStart)
			{
				var depth = 0;

				while (curNode.nextSibling === null && curNode.parentNode !== last)
				{
					depth++;
					curNode = curNode.parentNode;
				}

				// the current one is a block element
				// and we can move further? do it.
				if ($.inArray(curNode.nodeName.toLowerCase(), blocks) > -1 &&
					curNode.nextSibling !== null)
				{
					curNode = curNode.nextSibling;
				}

				// and back down into the blocks
				while (depth && curNode.firstChild && curNode.firstChild.nodeName.toLowerCase() != 'br')
				{
					depth--;
					curNode = curNode.firstChild;
				}
			}
		}

		curNodeLen = curNode.nodeValue ? curNode.nodeValue.length : 0;
		return [curNode, curNodeLen + offset];
	}
};

WysiHat.Selection.constructor = WysiHat.Selection;


/**
 * Editor Commands
 *
 * Container for reasonable base commands, such as bold, italicize,
 * and others. Currently also contains a normalized execCommand
 * function that may be moved to the browser normalization section.
 *
 * These currently extend the editor element, so you can call any
 * of them: $editor.boldSelection().
 */

WysiHat.Commands = (function() {

	// setup the empty objects
	var Commands = {
		is: {},
		make: {}
	};

	// List used to autogenerate a bunch of is* and make*
	// commands that all follow the same pattern.
	var auto = {

		// no frills mapping to execCommand
		makeEasy: [
			'bold', 'underline', 'italic', 'strikethrough', 'fontname',
			'fontsize', 'forecolor', 'createLink', 'insertImage',
			'insertOrderedList', 'insertUnorderedList'
		],

		// selectors to use for selectionIsWithin
		isSelectors: {
			'bold':	'b, strong',
			'italic': 'i, em',
			'link': 'a[href]',
			'underline': 'u, ins',
			'indented': 'blockquote',
			'strikethrough': 's, del',
			'orderedList': 'ol',
			'unorderedList': 'ul'
		},

		// native queryCommandState options
		isNativeState: {
			'bold': 'bold',
			'italic': 'italic',
			'underline': 'underline',
			'strikethrough': 'strikethrough',
			'orderedList': 'insertOrderedList',
			'unorderedList': 'insertUnorderedList'
		}
	};

	// Fill in the simple make() commands
	$.each(auto.makeEasy, function(i, v) {
		Commands.make[v] = function(param) {
			Commands.execCommand(v, false, param);
		}
	});

	// Fill in the simple is() commands
	$.each(auto.isSelectors, function(k, v) {
		if (k in auto.isNativeState)
		{
			Commands.is[k] = function() {
				return (Commands.selectionIsWithin(v) || document.queryCommandState(auto.isNativeState[k]));
			};
		}
		else
		{
			Commands.is[k] = function() {
				return Commands.selectionIsWithin(v);
			};
		}
	});

	// Setup a few aliases for nicer usage
	// i.e. this.is('underlined')
	var aliases = {
		is: {
			'linked': 'link',
			'underlined': 'underline',
			'struckthrough': 'strikethrough',
			'ol': 'orderedList',
			'ul': 'unorderedList'
		},

		make: {
			'italicize': 'italic',
			'font': 'fontname',
			'color': 'forecolor',
			'link': 'createLink',
			'ol': 'insertOrderedList',
			'ul': 'insertUnorderedList',
			'orderedList': 'insertOrderedList',
			'unorderedList': 'insertUnorderedList',
			'align': 'alignment'
		}
	};

	// add the aliases to the is and make objects
	$.each(aliases.is, function(k, v) {
		Commands.is[k] = function() {
			return Commands.is[v]();
		}
	});

	$.each(aliases.make, function(k, v) {
		Commands.make[k] = $.proxy(Commands.make, v);
	});


	// Do some feature detection for styling
	// with css instead of font tags
	Commands.noSpans = (function() {
		try {
			document.execCommand('styleWithCSS', 0, false);
			return function(){
				document.execCommand('styleWithCSS', 0, false);
			};
		} catch (e) {
			try {
				document.execCommand('useCSS', 0, true);
				return function(){
					document.execCommand('useCSS', 0, true);
				};
			} catch (e) {
				try {
					document.execCommand('styleWithCSS', false, false);
					return function(){
						document.execCommand('styleWithCSS', false, false);
					};
				}
				catch (e) {
					return $.noop;
				}
			}
		}
	})();

	// and return that for now, we'll extend it with
	// some handwritten custom methods below.
	return Commands;

})();

/**
 * Add the more complex commands
 */
$.extend(WysiHat.Commands, {

	_blockElements: WysiHat.Element.getContentElements().join(',').replace(',div,', ',div:not(.' + WysiHat.name + '-editor' + '),'),

	// Map to make sense of weird property names
	styleSelectors: {
		fontname:		'fontFamily',
		fontsize:		'fontSize',
		forecolor:		'color',
		hilitecolor:	'backgroundColor',
		backcolor:		'backgroundColor'
	},

	// Valid commands to execCommand
	validCommands: [
		'backColor', 'bold', 'createLink', 'fontName', 'fontSize', 'foreColor', 'hiliteColor',
		'italic', 'removeFormat', 'strikethrough', 'subscript', 'superscript', 'underline', 'unlink',
		'delete', 'formatBlock', 'forwardDelete', 'indent', 'insertHorizontalRule', 'insertHTML',
		'insertImage', 'insertLineBreak', 'insertOrderedList', 'insertParagraph', 'insertText',
		'insertUnorderedList', 'justifyCenter', 'justifyFull', 'justifyLeft', 'justifyRight', 'outdent',
		'copy', 'cut', 'paste', 'selectAll', 'styleWithCSS', 'useCSS'
	],

	/**
	 * Just like the standard browser execCommand
	 * with some precaucions.
	 */
	execCommand: function(command, ui, value) {
		this.noSpans();

		try {
			document.execCommand(command, ui, value);
		}
		catch(e)
		{
			return null;
		}
	},

	isMakeCommand: function(cmd)
	{
		return (cmd in this.make);
	},

	isValidExecCommand: function(cmd)
	{
		return ($.inArray(cmd, this.validCommands) > -1);
	},

	queryCommandState: function(state)
	{
		if (state in this.is)
		{
			return this.is[state]();
		}

		try {
			return document.queryCommandState(state);
		}
		catch(e) {
			return null;
		}
	},

	/**
	 * Takes the current selection and checks if it is
	 * within a selector given as a parameter.
	 */
	selectionIsWithin: function(tagNames)
	{
		var
		phrases	= WysiHat.Element.getPhraseElements(),
		phrase	= false,
		tags	= tagNames.split(','),
		t		= tags.length,
		sel		= window.getSelection(),
		a		= sel.anchorNode,
		b		= sel.focusNode;

		if ( a &&
			 a.nodeType &&
			 a.nodeType == 3 &&
			 a.nodeValue == '' )
		{
			a = a.nextSibling;
		}

		if ( ! a )
		{
			return false;
		}

		if ( $.browser.mozilla )
		{
			while ( t-- )
			{
				if ( $.inArray( tags[t], phrases ) != -1 )
				{
					phrase = true;
					break;
				}
			}
			if ( phrase &&
				 a.nodeType == 1 &&
				 $.inArray( a.nodeName.toLowerCase(), phrases ) == -1 )
			{
				t = a.firstChild;
				if ( t )
				{
					if ( t.nodeValue == '' )
					{
						t = t.nextSibling;
					}
					if ( t.nodeType == 1 )
					{
						a = t;
					}
				}
			}
		}

		while ( a &&
				b &&
				a.nodeType != 1 &&
			 	b.nodeType != 1 )
		{
			if ( a.nodeType != 1 )
			{
				a = a.parentNode;
			}
			if ( b.nodeType != 1 )
			{
				b = b.parentNode;
			}
		}
		return !! ( $(a).closest( tagNames ).length ||
		 			$(b).closest( tagNames ).length );
	},

	getSelectedStyles: function()
	{
		var
		selection = window.getSelection(),
		$node = $(selection.getNode()),
		styles = {};

		for (var s in this.styleSelectors) {
			styles[s] = $node.css(this.styleSelectors[s]);
		}
		return styles;
	},

	replaceElement: function( $el, tagName )
	{
		if ( $el.hasClass(WysiHat.name + '-editor') )
		{
			return;
		}

		var
		old		= $el.get(0),
		$newEl	= $('<'+tagName+'/>').html(old.innerHTML),
		attrs	= old.attributes,
		len		= attrs.length || 0;

		while (len--)
		{
			$newEl.attr( attrs[len].name, attrs[len].value );
		}

		$el.replaceWith( $newEl );

		return $newEl;
	},

	/**
	 * Is a bit of a misnamed method. It really acts more
	 * like an unwarp. The element is deleted, but the
	 * contents stay intact!
	 */
	deleteElement: function(el)
	{
		var $el = $(el);
		$el.replaceWith( $el.html() );
	},

	/**
	 * Completely strips the editor of formatting.
	 * This is used primarly by the remove formatting
	 * button.
	 */
	stripFormattingElements: function()
	{
		var that = this;

		function stripFormatters( i, el )
		{
			var $el = $(el);

			$el.children().each(stripFormatters);

			if ( isFormatter( $el ) )
			{
				that.deleteElement( $el );
			}
		}

		var
		selection	= window.getSelection(),
		isFormatter	= WysiHat.Element.isFormatter,
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range = selection.getRangeAt( i );
			ranges.push( range );
			this.getRangeElements( range, this._blockElements ).each( stripFormatters );
		}

		this.restoreRanges( ranges );
	},

	/**
	 * Allows you to manipulate the current
	 * selection range by range and then resets
	 * the original selection.
	 */
	manipulateSelection: function()
	{
		var
		selection	= window.getSelection(),
		i			= selection.rangeCount,
		ranges		= [],
		args		= arguments,
		callback	= args[0],
		range;

		while ( i-- )
		{
			range	= selection.getRangeAt( i );
			ranges.push( range );

			args[0] = range;

			callback.apply( this, args );
		}

		this.restoreRanges( ranges );
	},

	getRangeElements: function(range, tagNames)
	{
		var
		$from	= $( range.startContainer ).closest( tagNames ),
		$to		= $( range.endContainer ).closest( tagNames ),
		$els	= $('nullset');

		if ( !! $from.parents('.WysiHat-editor').length &&
		 	 !! $to.parents('.WysiHat-editor').length )
		{
			$els = $from;

			if ( ! $from.filter( $to ).length )
			{
				if ( $from.nextAll().filter( $to ).length )
				{
					$els = $from.nextUntil( $to ).andSelf().add( $to );
				}
				else
				{
					$els = $from.prevUntil( $to ).andSelf().add( $to );
				}
			}
		}

		return $els;
	},

	/**
	 * Grabs all ranges in the current selection and
	 * returns them as a usable array.
	 */
	getRanges: function()
	{
		var
		selection	= window.getSelection(),
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range = selection.getRangeAt( i );
			ranges.push( range );
		}

		return ranges;
	},

	/**
	 * Removes all ranges that may have been created
	 * in the editing process and replaces them with
	 * saved ranges passed in by the dev.
	 */
	restoreRanges: function(ranges)
	{
		var
		selection = window.getSelection(),
		i = ranges.length;

		selection.removeAllRanges();
		while ( i-- )
		{
			selection.addRange( ranges[i] );
		}
	},

	/**
	 * Changes the parent html block element
	 * into the one passed in by the dev.
	 * Ex. Use to flip headings.
	 */
	changeContentBlock: function(tagName)
	{
		var
		selection	= window.getSelection(),
		editor		= this,
		$editor		= $(editor),
		replaced	= 'WysiHat-replaced',
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range	= selection.getRangeAt( i );
			ranges.push( range );

			this.getRangeElements( range, this._blockElements )
				.each(function(){
					editor.replaceElement( $(this), tagName );
				 })
				.data( replaced, true );

		}
		$editor
			.children( tagName )
			.removeData( replaced );

		this.restoreRanges( ranges );
	},

	/**
	 * Utility function to get back to a paragraph state
	 */
	unformatContentBlock: function()
	{
		this.changeContentBlock('p');
	},

	/**
	 * @todo I don't like name and placement -pk
	 */
	unlinkSelection: function()
	{
		this.manipulateSelection(function( range ){
			this.getRangeElements( range, '[href]' ).each(this.clearElement);
		});
	},

	/**
	 * Wrap the current selection in some html
	 */
	wrapHTML: function()
	{
		var
		selection	= window.getSelection(),
		range		= selection.getRangeAt(0),
		node		= selection.getNode(),
		argLength	= arguments.length,
		el;

		if (range.collapsed)
		{
			range = document.createRange();
			range.selectNodeContents(node);
			selection.removeAllRanges();
			selection.addRange(range);
		}
		range = selection.getRangeAt(0);
		while ( argLength-- )
		{
			el = $('<' + arguments[argLength] + '/>');
			range.surroundContents( el.get(0) );
		}
	},

	/**
	 * Toggle between the editor and the textarea.
	 */
	toggleHTML: function(obj)
	{
		var
		$editor	= obj.$editor,
		$btn	= obj.$element,
		$field	= $editor.data('field'),
		$tools	= $btn.siblings(),
		text	= $btn.data('text');

		if ($editor.is(':visible'))
		{
			$btn.find('b').text($btn.data('toggle-text'));
			$tools.parents('ul.toolbar').addClass('disabled');
			$editor.hide();
			$field.show();
		}
		else
		{
			$btn.find('b').text(text);
			$tools.parents('ul.toolbar').removeClass('disabled');
			$field.hide();
			$editor.show();
		}
	},

	insertHTML: function(html)
	{
		if ( $.browser.msie )
		{
			var range = document.selection.createRange();
			range.pasteHTML(html);
			range.collapse(false);
			range.select();
		}
		else
		{
			this.execCommand('insertHTML', false, html);
		}
	},

	quoteSelection: function()
	{
		var $quote = $('<blockquote/>');
		this.manipulateSelection(function( range, $quote ){
			var
			$q		= $quote.clone(),
			$els	= this.getRangeElements( range, this._blockElements ),
			last	= $els.length - 1,
			$coll	= $();

			$els.each(function(i){
				var
				$this	= $(this),
				sub		= false,
				$el;

				if ( WysiHat.Element.isSubContainer( $this ) )
				{
					sub = true;
				}

				if ( ! i &&
					 sub &&
					 i == last )
				{
					$el = $('<p/>').html( $this.html() );
					$this.html('').append( $el );
					$coll = $coll.add($el);
				}
				else if ( sub )
				{
					$coll = $coll.add(
						$this.closest( WysiHat.Element.getContainers().join(",") )
					);
				}
				else
				{
					$coll = $coll.add($this);
				}

				if ( i == last )
				{
					$coll.wrapAll( $q );
				}
			});
		}, $quote);
	},

	unquoteSelection: function()
	{
		this.manipulateSelection(function( range ){
			this.getRangeElements( range, 'blockquote > *' ).each(function(){
				var
				el		= this,
				$el		= $(el),
				$parent	= $el.closest('blockquote'),
				$bq		= $parent.clone().html(''),
				$sibs	= $parent.children(),
				last	= $sibs.length - 1,
				$coll	= $();

				$el.unwrap('blockquote');

				if ( last > 0 )
				{
					$sibs.each(function(i){
						if ( this != el )
						{
							$coll = $coll.add(this);
						}

						if ( i == last || this == el )
						{
							$coll.wrapAll($bq.clone());
							$coll = $();
						}
					});
				}

				$parent = $el.parent();
				if ( WysiHat.Element.isSubContainer( $parent ) &&
				 	 $parent.children().length == 1 )
				{
					$parent.html($el.html());
				}
			});
		});
	}
});

/**
 * A few more make methods that either alias to some
 * larger top level stuff or couldn't quite be auto
 * generated.
 */
$.extend(WysiHat.Commands.make, {
	blockquote: function() {
		if (WysiHat.Commands.is.indented())
		{
			WysiHat.Commands.unquoteSelection();
		}
		else
		{
			WysiHat.Commands.quoteSelection();
		}
	},

	alignment: function(alignment)
	{
		WysiHat.Commands.execCommand('justify' + alignment);
	},

	backgroundColor: function(color)
	{
		var cmd = $.browser.mozilla ? 'hilitecolor' : 'backcolor';
		WysiHat.Commands.execCommand(cmd, false, color);
	}
});


/**
 * Commands Mixin
 *
 * Prettier solution to working with the basic manipulations.
 *
 * The old version of WysiHat had a boatload of fooSelection()
 * and isFoo() methods. It got a little unweidy, especially as
 * they were extended directly onto the editor jquery result.
 *
 * The above fixes most of that, but in order to smooth out the
 * bumps a bit more, I'm giving both the editor and the buttons
 * a single is() and make() api.
 *
 * They still have access to this.Commands for more advanced
 * manipulations.
 *
 * this.is('italic');
 * this.make('italic');
 * this.toggle('blockquote');
 *
 * this.Commands.advancedStuff();
 */

var CommandsMixin = {

	/**
	 * Better solution for what used to be a bunch
	 * of isFooBar() methods:
	 * this.is('bold')
	 */
	is: function(which) {
		return WysiHat.Commands.is[which]();
	},

	/**
	 * Nice method for doing built-in manipulations
	 * such as: this.make('bold')
	 */
	make: function(which, param) {
		return WysiHat.Commands.make[which](param);
	},

	/**
	 * Same as make, but makes more sense
	 * for some: this.toggle('blockquote')
	 */
	toggle: function(which, param) {
		return WysiHat.Commands.make[which](param);
	}
};

$.extend(WysiHat.Editor.prototype, CommandsMixin);

/**
 * Formatting Class
 *
 * Responsible for keeping the markup clean and compliant. Also
 * deals with keeping changes between the raw text and editor in
 * sync periodically.
 */

WysiHat.Formatting = {


	_bottomUp: function($parent, selector, callback)
	{
		var els = $parent.find(selector),
			rev = $.makeArray(els).reverse();

		$.each(rev, callback);
	},

	cleanup: function($element)
	{
		var replaceElement = WysiHat.Commands.replaceElement,
			deleteElement = WysiHat.Commands.deleteElement;

		// kill comments
		$element.contents().filter(function() {
			return this.nodeType == Node.COMMENT_NODE;
		}).remove();

		this._bottomUp($element, 'span', function()
		{
			var $this = $(this),
				fontWeight = $this.css('font-weight'),
				isBold = (fontWeight == 'bold' || fontWeight > 500),
				isItalic = ($this.css('font-style') == 'italic');

			if ( $this.hasClass('Apple-style-span') )
			{
				$this.removeClass('Apple-style-span');
			}

			$this.removeAttr('style');

			if (isItalic && isBold)
			{
				$this.wrap('<b>');
				replaceElement($this, 'i');
			}
			else if (isBold)
			{
				replaceElement($this, 'b');
			}
			else if (isItalic)
			{
				replaceElement($this, 'i');
			}
		});

		$element
			.children('div')
				.each(function(){
				 	if ( ! this.attributes.length )
				 	{
				 		replaceElement( $(this), 'p' );
				 	}
				 })
				.end()
			.find('strong')
				.each(function(){
				 	replaceElement($(this), 'b');
				 })
				.end()
			.find('em')
				.each(function(){
				 	replaceElement($(this), 'i');
				 })
				.end()
			.find('strike')
				.each(function(){
				 	replaceElement($(this), 'del');
				 })
				.end()
			.find('u')
				.each(function(){
				 	replaceElement($(this), 'ins');
				 })
				.end()
			.find('p:empty,script,noscript,style').remove();

		// firefox will sometimes end up nesting identical
		// tags. Let's not do that, please.
		$element.find('b > b, i > i').each(function() {
			deleteElement(this);
		});
	},

	// selection before tag, between tags, after tags
	// between tags (x offset)

	cleanupPaste: function($element, parentTagName)
	{
		var replaceElement = WysiHat.Commands.replaceElement;
		this.cleanup($element);

		// Ok, now we want to get rid of everything except for the
		// bare tags (with some exceptions, but not many). The trick
		// is to run through the found elements backwards. Otherwise
		// the node reference disappears when the parent is replaced.

		this._bottomUp($element, '*', function() {
			var nodeName = this.nodeName.toLowerCase(),
				replace = document.createElement(nodeName);

			switch (nodeName) {
				case 'a':
					replace.href = this.href;
					replace.title = this.title;
					break;
				case 'img':
					replace.src = this.src;
					replace.alt = this.alt;
					break;
				default:
					; // no attributes for you (on* would be dangerous)
			}

			replace.innerHTML = this.innerHTML;
			$(this).replaceWith(replace);
		});


		// most of this deals with newlines, start
		// out with a reasonable subset
		$element.find('br').replaceWith('\n');
		$element.html(function(i, html) {
			html = $.trim(html);
			html = html
				.replace(/<\/p>\s*<p>/g, '\n\n')
				.replace(/^(<p>)+/, '')			// some browsers automatically wrap every line in
				.replace(/(<\/p>)+$/, '')		// paragraphs, remove the outer ones, we don't want those.
				.replace(/<!--[^>]*-->/g, '');	// remove comments

			// no newlines, no paragraphs, no nonsense
			if (html.indexOf('\n') == -1) {
				return html;
			}

			// with the single line case out of the way, convert everything
			// to paragraphs. This will make weeding out the double newlines
			// easier below. I know it seems silly. By the end of this we're
			// back to input for safari, but normalized for all others.
			html = html
				.replace(/\n/, "<p>")
				.replace(/\n/g, "\n</p><p>");

			return $.trim(html) + "</p>";
		});

		// remove needless spans and empty elements
		$element.find('span').children(WysiHat.Element.getBlocks()).unwrap();
		$element.find(':empty').remove();

		// on reinsertion we need to check for identically nested elements
		// and clean those up. Otherwise pasting an h1 into an h1 is a clusterf***
		if (parentTagName.toLowerCase() != 'p')
		{
			$element
			.find(parentTagName).replaceWith(function(i, inner) {
				return inner;
			});
		}

		// ok, now the fun bit with the paragraphs and newlines again.
		// We equalize all newlines into paragraphs above, but really
		// we only want them for the doubles newlines. All others are
		// supposed to be <br>s. So we need to step through all the
		// sibling pairs and merge when they are not separated by a blank.

		var currentP,
			removal = [];

		// if previous blank, start new one
		// if previous not blank, add to previous

		$element.find('p ~ p').each(function() {
			var $this = $(this),
				$prev = $this.prev();

			if ( ! currentP)
			{
				currentP = $prev;
			}
			else if ( ! $.trim($prev.html()))
			{
				currentP.after('\n');
				currentP = removal.pop();
			}

			currentP.html(function(i, val) {
				var html = $.trim($this.html());
				val = $.trim(val);

				// both have contents? add a newline between them
				if (val && html)
				{
					val += '<br>';
				}

				return val + html;
			});

			removal.push($this);
		});

		// we no longer need these
		while (currentP = removal.pop())
		{
			currentP.remove();
		}

		// since all of the code above was newline sensitive, what
		// comes out has none. So make it pretty!
		$element
			.before("\n")
			.find("br").replaceWith('<br>\n');
	},

	reBlocks: new RegExp(
		'(<\/(?:ul|ol)>|<\/(?:' + WysiHat.Element.getBlocks().join('|') + ')>)',
		'g'
	),

	format: function( $el )
	{
		var that = this;

		$el.html(function(i, old) {
			return old
				// lowercase all tags
				.replace( /<\/?[A-Z]+/g, function(tag) {
					return tag.toLowerCase();
				})
				// cleanup whitespace and empty tags
				.replace(/(\t|\n| )+/g, ' ')			// reduce whitespace to spaces
				.replace(/>\s+</g, '> <')				// reduce whitespace next to tags
				.replace('/&nbsp;/g', ' ')				// remove non-breaking spaces
				.replace('/<p>[ ]+</p>/g', '')			// remove empty paragraphs
				.replace(/<br ?\/?>\s?<\/p>/g, '</p>')	// remove brs at ends of paragraphs
				.replace(/<p>\n+<\/p>/g, '')			// remove paragraphs full of newlines
				.replace(that.reBlocks, '$1\n\n')		// line between blocks
				.replace(/<br ?\/?>/g, '<br>\n')		// newlines after brs

				// prettify lists
				.replace(/(ul|ol|li)>\s+<(\/)?(ul|ol|li)>/g, '$1>\n<$2$3>')
				.replace(/><li>/g, '>\n<li>')
				.replace(/<\/li>\n+</g, '</li>\n<')
				.replace(/^\s+(<li>|<\/?ul>|<\/?ol>)/gm, '$1')
				.replace(/<li>/g, '    <li>')

				// prettify tables
				.replace(/>\s*(<\/?tr>)/g, '>$1')
				.replace(/(<\/?tr>)\s*</g, '$1<')
				.replace(/<(\/?(table|tbody))>/g, '<$1>\n')
				.replace(/<\/tr>/g, '<\/tr>\n')
				.replace(/<tr>/g, '    <tr>');
		});

		// Remove the extra white space that gets added after the
		// last block in the .replace(that.reBlocks, '$1\n\n') line.
		// If we don't remove it, then it sticks around and eventually
		// becomes a new paragraph.  Which is just annoying.
		$el.html($el.html().trim());

	},

	getBrowserMarkupFrom: function( $el )
	{
		var $container = $('<div>' + $el.val()+ '</div>'),
			html;

		this.cleanup($container);
		html = $container.html();

		if (html == '' ||
		 	html == '<br>' ||
		 	html == '<br/>' )
		{
			$container.html('<p>&#x200b;</p>');
		}

		return $container.html();
	},

	getApplicationMarkupFrom: function( $el )
	{
		var
		$clone = $el.clone(),
		$container,
		html;

		$container = $('<div/>').html($clone.html());
		html = $container.html();

		if (html == '' ||
		 	html == '<br>' ||
		 	html == '<br/>' )
		{
			$container.html('<p>&#x200b;</p>');
		}

		this.cleanup( $container );
		this.format( $container );

		return $container
				.html()
				.replace( /<\/?[A-Z]+/g, function(tag) {
					return tag.toLowerCase();
				 });
	}
};

/**
 * Blank Button
 *
 * The base prototype for all buttons. Handles the basic init and
 * provides a nice way to extend the buttons without having to re-
 * do all of the work the toolbar does.
 */

var BlankButton = {
	init: function(name, $editor)
	{
		this.name = name;
		this.$editor = $editor;
		this.$field = $editor.data('field');
		return this;
	},

	setElement: function(el)
	{
		this.$element = $(el);
		return this;
	},

	getHandler: function()
	{
		if (this.handler)
		{
			return $.proxy(this, 'handler');
		}

		var that = this;

		if (WysiHat.Commands.isMakeCommand(this.name))
		{
			return function()
			{
				return WysiHat.Commands.make[that.name]();
			}
		}
		else if (WysiHat.Commands.isValidExecCommand(this.name))
		{
			return function()
			{
				return WysiHat.Commands.execCommand(that.name);
			};
		}

		return $.noop;
	},

	getStateHandler: function()
	{
		if (this.query)
		{
			return $.proxy(this, 'query');
		}
		else if ( WysiHat.Commands.isValidExecCommand( this.name ) )
		{
			var that = this;
			return function( $editor )
			{
				// @pk clean up
				var E = $editor.data('wysihat');
				return E.Commands.queryCommandState(that.name);
			};
		}

		return $.noop;
	},

	setOn: function() {
		this.$element
			.addClass('selected')
			.attr('aria-pressed','true')
			.find('b')
				.text(this['toggle-text'] ? this['toggle-text'] : this.label);
		return this;
	},

	setOff: function() {
		this.$element
			.removeClass('selected')
			.attr('aria-pressed','false')
			.find('b')
				.text(this.label);
		return this;
	}
};

/**
 * Toolbar Class
 *
 * Handles the creation of the toolbar and manages the individual
 * buttons states. You can add your own by using:
 * WysiHat.addButton(name, { options });
 */

WysiHat.Toolbar = function($el, buttons)
{
	this.suspendQueries = false;

	this.$editor = $el;
	this.$toolbar = $('<ul class="toolbar rte"></ul>');

	$el.before(this.$toolbar);

	// add buttons
	var l = buttons.length, i;

	for (i = 0 ; i < l; i++)
	{
		this.addButton(buttons[i]);
	}

	// Add .last to the last "normal" tool (not .rte-elements nor .rte-view)
	if (this.$toolbar.children('.rte-elements').length) {
		this.$toolbar.children('.rte-elements').prev().addClass('last');
	} else if (this.$toolbar.children('.rte-view').length) {
		this.$toolbar.children('.rte-view').prev().addClass('last');
	} else {
		this.$toolbar.children('li:last').addClass('last');
	}
}

WysiHat.Toolbar.prototype = {

	addButton: function(name)
	{
		var Editor = this.$editor.data('wysihat');

		var $button, stateHandler,
			button = WysiHat.inherit(BlankButton, WysiHat._buttons[name]).init(name, Editor.$editor);

		$.extend(button, CommandsMixin);

		// Add utility references straight onto the button
		button.Editor = Editor;
		button.Event = Editor.Event;
		button.Commands = Editor.Commands;
		button.Selection = Editor.Selection;

		button.setElement( this.createButtonElement(button) );
		button.Event.add(name, button.getHandler());

		this.observeButtonClick(button);
		this.observeStateChanges(button);
	},

	createButtonElement: function(button)
	{
		var $btn;

		if (button.type && button.type == 'select')
		{
			var opts = button.options,
				l = opts.length, i = 0;

			$btn = $('<select/>');

			for ( ; i < l; i++)
			{
				$btn.append(
					'<option value="' + opts[i][0] + '">' + opts[i][1] + '</option>'
				);
			}

			$btn.appendTo(this.$toolbar)
				.wrap('<li class="rte-elements"/>');
		}
		else
		{
			$btn = $('<li><a href=""></a></li>');

			$btn.appendTo(this.$toolbar);
		}

		if (button.cssClass)
		{
			$btn.addClass(button.cssClass);
		}

		if (button.title)
		{
			$btn.find('a').attr('title', button.title);
		}

		$btn.data('text', button.label);
		if (button['toggle-text'])
		{
			$btn.data('toggle-text', button['toggle-text']);
		}

		return $btn;
	},

	observeButtonClick: function(button)
	{
		var evt = (button.type && button.type == 'select') ? 'change' : 'click',
			that = this;

		button.$element.on(evt, function(e) {
			// IE had trouble doing change handlers
			// as the state check would run too soon
			// and reset the input element, so we suspend
			// the query checks until after the event handler
			// has run.
			that.suspendQueries = true;

			var $editor = button.$editor;

			// Bring focus to the editor before the handler is called
			// so that selection data is available to tools
			if ( ! $editor.is(':focus'))
			{
				$editor.focus();
			}

			button.Event.fire(button.name);

			that.suspendQueries = false;

			return false;
		});
	},

	observeStateChanges: function(button)
	{
		var
		that = this,
		handler = button.getStateHandler(),
		previousState;

		that.$editor.on( 'WysiHat-selection:change', function(){
			if (that.suspendQueries)
			{
				return;
			}

			var state = handler( button.$editor, button.$element );
			if (state != previousState)
			{
				previousState = state;
				that.updateButtonState(button, state);
			}
		});
	},

	updateButtonState: function(button, state)
	{
		if (state)
		{
			button.setOn();
			return;
		}

		button.setOff();
	}
};

WysiHat.Toolbar.constructor = WysiHat.Toolbar;



})(document, jQuery);

/**
 * Defaults and jQuery Binding
 *
 * This code sets up reasonable editor defaults and then adds
 * a convenience setup function to jQuery.fn that you can use
 * as $('textarea').wysihat(options).
 */

jQuery.fn.wysihat = function(options) {

	var el = this.data('wysihat');

	if (el)
	{
		if (jQuery.inArray(options, ['Event', 'Selection', 'Toolbar', 'Undo']) != -1)
		{
			return el[options];
		}

		return el;
	}

	return this.each(function() {
		el = WysiHat.attach(this, options);
		$(this).data('wysihat', el);
	});
};



/**
 * Browser Compat Classes
 *
 * Below we normalize the Range and Selection classes to work
 * properly across all browsers. If you like IE, you'll feel
 * right at home down here.
 */


(function(document, $) {

if ( typeof Node == "undefined" )
{
	(function(){
		function Node(){
			return {
				ATTRIBUTE_NODE: 2,
				CDATA_SECTION_NODE: 4,
				COMMENT_NODE: 8,
				DOCUMENT_FRAGMENT_NODE: 11,
				DOCUMENT_NODE: 9,
				DOCUMENT_TYPE_NODE: 10,
				ELEMENT_NODE: 1,
				ENTITY_NODE: 6,
				ENTITY_REFERENCE_NODE: 5,
				NOTATION_NODE: 12,
				PROCESSING_INSTRUCTION_NODE: 7,
				TEXT_NODE: 3
			};
		};
		window.Node = new Node();
	})();
}

if ( ! document.getSelection) {

/**
 * Selection and Range Shims
 *
 * Big hat tips to Tim Down's Rangy and Tim Cameron
 * Ryan's IERange. Neither quite worked here so I
 * reimplemented it with lots of inspiration from
 * their code.
 *
 * Rangy and IERange are MIT Licensed
 */
(function() {


	/**
	 * Ranges. These are fun.
	 */
	function Range() {

		this.startContainer;
		this.startOffset;

		this.endContainer;
		this.endOffset;

		this.collapsed;
	}

	Range.prototype = {

		/**
		 * Set the beginning of the range
		 */
		setStart: function(container, offset)
		{
			this.startContainer = container;
			this.startOffset = offset;

			if (container == this.endContainer && offset == this.endOffset)
			{
				this.collapsed = true;
			}
		},

		/**
		 * Set the end of the range
		 */
		setEnd: function(container, offset)
		{
			this.endContainer = container;
			this.endOffset = offset;

			if (container == this.startContainer && offset == this.startOffset)
			{
				this.collapsed = true;
			}
		},

		/**
		 * Collapse the range
		 */
		collapse: function(toBeginning)
		{
			if (toBeginning)
			{
				// move to beginning
				this.endContainer = this.startContainer;
				this.endOffset = this.startOffset;
			}
			else
			{
				// move to end
				this.startContainer = this.endContainer;
				this.startOffset = this.endOffset;
			}
		},

		/**
		 * Get the containing node
		 */
		getNode: function()
		{
			var textRange = document.selection.createRange();
			return CompatUtil.getParentElement(textRange);
		},

		/**
		 * Select a specific node
		 */
		selectNode: function(node)
		{
			this.setStart(node.parentNode, CompatUtil.getNodeIndex(node));
			this.setEnd(node.parentNode, CompatUtil.getNodeIndex(node) + 1);
		},

		insertNode: function(node)
		{
			CompatUtil.insertNode(node, this.startContainer, this.startOffset);
		},

		/**
		 * Select a node's contents
		 */
		selectNodeContents: function(node)
		{
			var l = CompatUtil.isCharacterDataNode(node) ? node.length : node.childNodes.length;
			this.setStart(node, 0);
			this.setEnd(node, l);
		},

		surroundContents: function(node)
		{
			// @pk @todo implement
		},

		/**
		 * Grab a copy of this Range
		 */
		cloneRange: function()
		{
			var range = new Range();

			range.setStart(this.startContainer, this.startOffset);
			range.setEnd(this.endContainer, this.endOffset);

			return range;
		},

		/**
		 * Get the text content
		 */
		toString: function()
		{
			var tr = CompatUtil.rangeToTextRange(this);
			return tr ? tr.text : '';
		}
	};


	/**
	 * Open the range getter up to the public.
	 */
	document.createRange = function() {
		return new Range();
	};


	/**
	 * And now selections! Wahoo!
	 */
	function Selection() {
		this._reset();
		this._selection = document.selection;
	}

	Selection.prototype = {

		/**
		 * Sort of an init / reset.
		 *
		 * Selections are singletons so their
		 * state is very fragile.
		 */
		_reset: function()
		{
			this.rangeCount = 0;

			this.anchorNode = null;
			this.anchorOffset = null;

			this.focusNode = null;
			this.focusOffset = null;

			// implementation
			this._ranges = [];
		},

		/**
		 * Add a range to the visible selection
		 */
		addRange: function(range)
		{
			var tr = CompatUtil.rangeToTextRange(range);

			if ( ! tr)
			{
				this.removeAllRanges();
				return;
			}

			tr.select();

			// Check for intersection with old?
			// Skipping it for now, I don't think we
			// ever use them that way. If you decide to
			// add it, I suggest riffing off webkit's
			// webcore DOMSelection::addRange logic. -pk

			this.rangeCount = 1;
			this._ranges = [range];
			this.isCollapsed = range.collapsed;

			this._updateNodeRefs(range);
		},

		/**
		 * Deselect Everything
		 */
		removeAllRanges: function()
		{
			if (this.rangeCount)
			{
				this._selection.empty();
			}

			this._reset();
		},

		/**
		 * Firefox supports more than one range in a selection.
		 * We do not.
		 */
		getRangeAt: function(index)
		{
			if (index !== 0)
			{
				return null;
			}

			return this._ranges[index];
		},

		/**
		 * Get the string contents
		 */
		toString: function()
		{
			// grab range contents
			if (this.rangeCount)
			{
				return this._ranges[0].toString();
			}

			return '';
		},

		/**
		 * Refresh the selection state
		 *
		 * There is only one selection per window, so we call
		 * this every time the user asks for a selection through
		 * getSelection.
		 */
		_refresh: function()
		{
			// the TextRange parentElement implementation is bugtastic, so
			// we need to do this manually ...

			var textRange = this._selection.createRange(),
				Container = CompatUtil.getParentElement(textRange),
				start, end, range;

			// is collapsed?
			if (textRange.compareEndPoints("StartToEnd", textRange) == 0)
			{
				start = CompatUtil.getBoundary(textRange, Container, true, true);
				end = start;
			}
			else
			{
				start = CompatUtil.getBoundary(textRange, Container, true, false);
				end = CompatUtil.getBoundary(textRange, Container, false, false);
			}

			var range = new Range();
			range.setStart(start.node, start.offset);
			range.setEnd(end.node, end.offset);

			this.rangeCount = 1;
			this._ranges = [range];
			this.isCollapsed = range.collapsed;

			this._updateNodeRefs(range);

			return this;
		},

		/**
		 * Sync the nodes and offsets
		 *
		 * For whatever reason the selection holds
		 * what amounts to duplicate data about the
		 * ranges. No magic __get in js, so we copy.
		 */
		_updateNodeRefs: function(range)
		{
			this.anchorNode = range.startContainer;
			this.anchorOffset = range.startOffset;

			this.focusNode = range.endContainer;
			this.focusOffset = range.endOffset;
		}
	};


	/**
	 * Open the selection getter up to the public.
	 *
	 * It is generally a good idea to grab a new selection
	 * if there is any chance of it being messed with. This
	 * applies doubly in this case because of the _refresh call.
	 */
	var S = new Selection();
	window.getSelection = function() {
		return S._refresh();
	};


	/**
	 * Dom Position Helper Object
	 *
	 * This can slowly be pulled out, but it's used in a few
	 * places and actually isn't too inconvenient.
	 */
	function DomPosition(node, offset)
	{
		this.node = node;
		this.offset = offset;
	}

	/**
	 * Some utility helper methods.
	 *
	 * Big, big hat tip to Rangy!
	 * http://code.google.com/p/rangy/
	 */
	var CompatUtil = {

		/**
		 * Character data nodes have text, others
		 * have childNodes.
		 */
		isCharacterDataNode: function(node)
		{
			var t = node.nodeType;
			return t == 3 || t == 4 || t == 8 ; // Text, CDataSection or Comment
		},

		/**
		 * Find a node offset for non-chardatanode
		 * selection offsets.
		 */
		getNodeIndex: function(node)
		{
			var i = 0;
			while((node = node.previousSibling))
			{
				i++;
			}
			return i;
		},

		/*
		 * Check for ancestors. May be able to move
		 * this to $.contains(ancestor, descendant) in the future.
		 */
		isAncestorOf: function(ancestor, descendant, selfIsAncestor)
		{
			var n = selfIsAncestor ? descendant : descendant.parentNode;
			while (n)
			{
				if (n === ancestor)
				{
					return true;
				}

				n = n.parentNode;
			}

			return false;
		},

		/**
		 * Find a shared ancestor
		 */
		getCommonAncestor: function(node1, node2)
		{
			var ancestors = [], n;
			for (n = node1; n; n = n.parentNode)
			{
				ancestors.push(n);
			}

			for (n = node2; n; n = n.parentNode)
			{
				if ($.inArray(n, ancestors) > -1)
				{
					return n;
				}
			}

			return null;
		},

		/*
		 * Insert the node at a specific offset.
		 * Needs to split text nodes if the insertion is to happen
		 * in the middle of some text.
		 */
		insertNode: function(node, n, o)
		{
			var firstNodeInserted = node.nodeType == 11 ? node.firstChild : node;
			if (this.isCharacterDataNode(n))
			{
				if (o == n.length)
				{
					$(node).insertAfter(n);
				}
				else
				{
					n.parentNode.insertBefore(node, o == 0 ? n : this.splitDataNode(n, o));
				}
			}
			else if (o >= n.childNodes.length)
			{
				n.appendChild(node);
			}
			else
			{
				n.insertBefore(node, n.childNodes[o]);
			}

			return firstNodeInserted;
		},

		/**
		 * Split a text, comment, or cdata node
		 * to make room for a new insertion.
		 */
		splitDataNode: function(node, index)
		{
			var newNode = node.cloneNode(false);
			newNode.deleteData(0, index);
			node.deleteData(index, node.length - index);
			$(newNode).insertAfter(node);
			return newNode;
		},

		/**
		 * Convert a range object back to a textRange
		 */
		rangeToTextRange: function(range)
		{
			var startRange, endRange;

			startRange = this.createBoundaryTextRange(new DomPosition(range.startContainer, range.startOffset), true);

			if (range.collapsed)
			{
				return startRange;
			}

			endRange = this.createBoundaryTextRange(new DomPosition(range.endContainer, range.endOffset), false);

			if ( ! startRange || ! endRange)
			{
				return false;
			}

			textRange = document.body.createTextRange();
			textRange.setEndPoint("StartToStart", startRange);
			textRange.setEndPoint("EndToEnd", endRange);
			return textRange;
		},

		/**
		 * IE's textRange.parentElement is buggy, so
		 * this function does a bit more work to ensure
		 * consistency.
		 */
		getParentElement: function(textRange)
		{
			var parentEl = textRange.parentElement(),
				startEndContainer,
				startEl, endEl,
				range;

			// find starting element
			range = textRange.duplicate();
			range.collapse(true);
			startEl = range.parentElement();

			// find ending element
			range = textRange.duplicate();
			range.collapse(false);
			endEl = range.parentElement();

			// find common parent
			startEndContainer = (startEl == endEl) ? startEl : this.getCommonAncestor(startEl, endEl);
			return startEndContainer == parentEl ? startEndContainer : this.getCommonAncestor(parentEl, startEndContainer);
		},

		/**
		 * Traverse the dom and place a textNode at the desired position.
		 */
		createBoundaryTextRange: function(boundaryPosition, isStart)
		{
			var doc = document,
				boundaryOffset = boundaryPosition.offset,
				workingRange = doc.body.createTextRange(),
				nodeIsDataNode = this.isCharacterDataNode(boundaryPosition.node),
				boundaryNode, boundaryParent,
				workingNode, childNodes;

			if (nodeIsDataNode)
			{
				boundaryNode = boundaryPosition.node;
				boundaryParent = boundaryNode.parentNode;
			}
			else
			{
				childNodes = boundaryPosition.node.childNodes;
				boundaryNode = (boundaryOffset < childNodes.length) ? childNodes[boundaryOffset] : null;
				boundaryParent = boundaryPosition.node;
			}

			// Position the range immediately before the node containing the boundary
			workingNode = doc.createElement("span");

			// Making the working element non-empty element persuades IE to consider the TextRange boundary to be within the
			// element rather than immediately before or after it, which is what we want
			workingNode.innerHTML = "&#feff;";

			// insertBefore is supposed to work like appendChild if the second parameter is null. However, a bug report
			// for IERange suggests that it can crash the browser: http://code.google.com/p/ierange/issues/detail?id=12

			if (boundaryNode)
			{
				boundaryParent.insertBefore(workingNode, boundaryNode);
			}
			else
			{
				boundaryParent.appendChild(workingNode);
			}

			if ( ! $.contains(document.body, workingNode))
			{
				// Clean up and bail
				boundaryParent.removeChild(workingNode);
				return null;
			}

			workingRange.moveToElementText(workingNode);
			workingRange.collapse(!isStart);

			// Clean up
			boundaryParent.removeChild(workingNode);

			// Move the working range to the text offset, if required
			if (nodeIsDataNode)
			{
				workingRange[isStart ? "moveStart" : "moveEnd"]("character", boundaryOffset);
			}

			return workingRange;
		},

		/**
		 * Gets the boundary of a TextRange expressed as a node and an offset within that node. This function started out as
		 * an improved version of code found in Tim Cameron Ryan's IERange (http://code.google.com/p/ierange/) but has
		 * grown, fixing problems with line breaks in preformatted text, adding workaround for IE TextRange bugs, handling
		 * for inputs and images, plus optimizations.
		 */
		getBoundary: function(textRange, wholeRangeContainerElement, isStart, isCollapsed)
		{
			var workingRange = textRange.duplicate(),
				containerElement;

			workingRange.collapse(isStart);
			containerElement = workingRange.parentElement();

			// Sometimes collapsing a TextRange that's at the start of a text node can move it into the previous node, so
			// check for that
			// TODO: Find out when. Workaround for wholeRangeContainerElement may break this
			if ( ! this.isAncestorOf(wholeRangeContainerElement, containerElement, true))
			{
				containerElement = wholeRangeContainerElement;
			}

			// Deal with nodes that cannot "contain rich HTML markup". In practice, this means form inputs, images and
			// similar. See http://msdn.microsoft.com/en-us/library/aa703950%28VS.85%29.aspx
			if ( ! containerElement.canHaveHTML)
			{
				return new DomPosition(containerElement.parentNode, this.getNodeIndex(containerElement));
			}

			var workingNode = document.createElement("span"),
				workingComparisonType = isStart ? "StartToStart" : "StartToEnd",
				comparison, previousNode, nextNode, boundaryPosition, boundaryNode;

			// Move the working range through the container's children, starting at the end and working backwards, until the
			// working range reaches or goes past the boundary we're interested in
			do
			{
				containerElement.insertBefore(workingNode, workingNode.previousSibling);
				workingRange.moveToElementText(workingNode);
			}
			while ((comparison = workingRange.compareEndPoints(workingComparisonType, textRange)) > 0 &&
					workingNode.previousSibling);

			// We've now reached or gone past the boundary of the text range we're interested in
			// so have identified the node we want
			boundaryNode = workingNode.nextSibling;

			if (comparison == -1 && boundaryNode && this.isCharacterDataNode(boundaryNode))
			{
				// This is a character data node (text, comment, cdata). The working range is collapsed at the start of the
				// node containing the text range's boundary, so we move the end of the working range to the boundary point
				// and measure the length of its text to get the boundary's offset within the node.
				workingRange.setEndPoint(isStart ? "EndToStart" : "EndToEnd", textRange);

				var offset;

				if (/[\r\n]/.test(boundaryNode.data))
				{
					/*
					For the particular case of a boundary within a text node containing line breaks (within a <pre> element,
					for example), we need a slightly complicated approach to get the boundary's offset in IE. The facts:

					- Each line break is represented as \r in the text node's data/nodeValue properties
					- Each line break is represented as \r\n in the TextRange's 'text' property
					- The 'text' property of the TextRange does not contain trailing line breaks

					To get round the problem presented by the final fact above, we can use the fact that TextRange's
					moveStart() and moveEnd() methods return the actual number of characters moved, which is not necessarily
					the same as the number of characters it was instructed to move. The simplest approach is to use this to
					store the characters moved when moving both the start and end of the range to the start of the document
					body and subtracting the start offset from the end offset (the "move-negative-gazillion" method).
					However, this is extremely slow when the document is large and the range is near the end of it. Clearly
					doing the mirror image (i.e. moving the range boundaries to the end of the document) has the same
					problem.

					Another approach that works is to use moveStart() to move the start boundary of the range up to the end
					boundary one character at a time and incrementing a counter with the value returned by the moveStart()
					call. However, the check for whether the start boundary has reached the end boundary is expensive, so
					this method is slow (although unlike "move-negative-gazillion" is largely unaffected by the location of
					the range within the document).

					The method below is a hybrid of the two methods above. It uses the fact that a string containing the
					TextRange's 'text' property with each \r\n converted to a single \r character cannot be longer than the
					text of the TextRange, so the start of the range is moved that length initially and then a character at
					a time to make up for any trailing line breaks not contained in the 'text' property. This has good
					performance in most situations compared to the previous two methods.
					*/
					var tempRange = workingRange.duplicate(),
						rangeLength = tempRange.text.replace(/\r\n/g, "\r").length;

					offset = tempRange.moveStart("character", rangeLength);
					while ((comparison = tempRange.compareEndPoints("StartToEnd", tempRange)) == -1)
					{
						offset++;
						tempRange.moveStart("character", 1);
					}
				}
				else
				{
					offset = workingRange.text.length;
				}

				boundaryPosition = new DomPosition(boundaryNode, offset);
			}
			else
			{
				// If the boundary immediately follows a character data node and this is the end boundary, we should favour
				// a position within that, and likewise for a start boundary preceding a character data node
				previousNode = (isCollapsed || !isStart) && workingNode.previousSibling;
				nextNode = (isCollapsed || isStart) && workingNode.nextSibling;

				if (nextNode && this.isCharacterDataNode(nextNode))
				{
					boundaryPosition = new DomPosition(nextNode, 0);
				}
				else if (previousNode && this.isCharacterDataNode(previousNode))
				{
					boundaryPosition = new DomPosition(previousNode, previousNode.length);
				}
				else
				{
					boundaryPosition = new DomPosition(containerElement, this.getNodeIndex(workingNode));
				}
			}

			// Clean up
			workingNode.parentNode.removeChild(workingNode);

			return boundaryPosition;
		}
	};

	// expose them for the trickery below
	window.Range = Range;
	window.Selection = Selection;

})();

} // end "if ( ! window.selection)"
else
{
	// quick fix so we can extend the native prototype
	window.Selection = {};
	window.Selection.prototype = window.getSelection().__proto__;
}


// Add a few more methods to all ranges and selections.
// Both native and our shims.

$.extend(Range.prototype, {

	/**
	 * Compare two ranges for equality. We want to
	 * compare the actual selection rather than just
	 * the offsets, since there is more than one way
	 * to specify a certain selection.
	 */
	equalRange: function(range)
	{
		if ( ! range ||
		! range.compareBoundaryPoints )
		{
			return false;
		}

		// if both ranges are collapsed we just need to compare one point
		if (this.collapsed && range.collapsed)
		{
			return (this.compareBoundaryPoints( this.START_TO_START, range ) == 0);
		}

		return ( this.compareBoundaryPoints( this.START_TO_START, range ) == 0 &&
			this.compareBoundaryPoints( this.START_TO_END, range ) == 1 &&
			this.compareBoundaryPoints( this.END_TO_END, range ) == 0 &&
			this.compareBoundaryPoints( this.END_TO_START, range ) == -1 );
	}
});

$.extend(window.Selection.prototype, {

	/**
	 * Get the node that most encompasses the
	 * entire selection.
	 */
	getNode: function()
	{
		return ( this.rangeCount > 0 ) ? this.getRangeAt(0).getNode() : null;
	}
});

/**
 * Add $.browser if it doesn't yet exist. This typically
 * happens on the frontend where our common.js isn't available
 */
$.uaMatch = $.uaMatch || function( ua ) {
	ua = ua.toLowerCase();

	var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
		/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
		/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
		/(msie) ([\w.]+)/.exec( ua ) ||
		ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
		[];

	return {
		browser: match[ 1 ] || "",
		version: match[ 2 ] || "0"
	};
};

// Don't clobber any existing $.browser in case it's different
if ( !$.browser ) {
	matched = $.uaMatch( navigator.userAgent );
	browser = {};

	if ( matched.browser ) {
		browser[ matched.browser ] = true;
		browser.version = matched.version;
	}

	// Chrome is Webkit, but Webkit is also Safari.
	if ( browser.chrome ) {
		browser.webkit = true;
	} else if ( browser.webkit ) {
		browser.safari = true;
	}

	$.browser = browser;
}

})(document, jQuery);
