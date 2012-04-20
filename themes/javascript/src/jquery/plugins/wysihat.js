/*  WysiHat - WYSIWYG JavaScript framework, version 0.2.1
 *  (c) 2008-2010 Joshua Peek
 *  JQ-WysiHat - jQuery port of WysiHat to run on jQuery
 *  (c) 2010 Scott Williams & Aaron Gustafson
 *
 *  WysiHat is freely distributable under the terms of an MIT-style license.
 *--------------------------------------------------------------------------*/

(function(document, $, undefined){

// ---------------------------------------------------------------------

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

// ---------------------------------------------------------------------

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


// ---------------------------------------------------------------------

/**
 * WysiHat.Editor
 *
 * The parent class of the editor. Instantiating it gets the whole
 * snafu going. Holds the textarea and editor objects as well as
 * all of the utility classes.
 */

// ---------------------------------------------------------------------

WysiHat.Editor = function($field, options) {
	this.$field = $field;
	this.$editor = this.create();

	$field.hide().before(this.$editor);

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
	_empty: '<p>&#x200b;</p>',

	/**
	 * Create the main editor html
	 */
	create: function() {
		return $('<div/>', {
			'class': WysiHat.name + '-editor',

			'data': {
				'wysihat': this,
				'field': this.$field
			},

			'role': 'application',
			'contentEditable': 'true',

			// Respect textarea's existing row count settings
			'height': this.$field.height(),

			'html': WysiHat.Formatting.getBrowserMarkupFrom(this.$field)
		});
	},

	/**
	 * Setup all of the utility classes
	 */
	init: function(options) {
		var $ed = this.$editor;

		this.Undo = new WysiHat.Undo();
		this.Selection = new WysiHat.Selection($ed);
		this.Event = new WysiHat.Event(this);
		this.Toolbar = new WysiHat.Toolbar($ed, options.buttons);

		this.$field.change($.proxy(this, 'updateEditor'));

		// if, on submit, the editor is active, we
		// need to sync to the field before sending the data
		$ed.closest('form').submit(function() {
			if ($ed.is(':visible'))
			{
				this.updateField();
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

		if ( val == '' ||
			 val == '<br>' ||
			 val == '<br/>' ||
			 val == '<p></p>' ||
			 val == '<p>\0</p>')
		{
			$el.html(this._empty);

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


// ---------------------------------------------------------------------

/**
 * Element Manager
 *
 * Holds information about available elements and can be used to
 * check if an element is of a valid type.
 */

// ---------------------------------------------------------------------

WysiHat.Element = (function(){

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
		while ( ret == false &&
				i-- > 1 )
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


// ---------------------------------------------------------------------

/**
 * Change Events
 *
 * Binds to various events to fire things such fieldChange and
 * editorChange. Currently also handles browser insertion for
 * empty events.
 *
 * Will probably be removed in favor of a real event system.
 */

// ---------------------------------------------------------------------

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


// ---------------------------------------------------------------------

/**
 * Paste Handler
 *
 * A paste helper utility. How this works, is that browsers will
 * fire paste before actually inserting the text. So that we can
 * quickly create a new contentEditable object that is outside the
 * viewport. Focus it. And the text will go in there. That makes
 * it much easier for us to clean up.
 */

// ---------------------------------------------------------------------

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

				$paster.html('').css('top', document.body.scrollTop);
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

					var $parentBlock = = $(startC).closest(WysiHat.Element.getBlocks().join(',')),
						html = Editor.$editor.html();
					
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

					if ( html == '' ||
						 html == '<br>' ||
						 html == '<br/>' ||
						 html == '<p></p>' ||
						 html == '<p>\0</p>' ||
						 html == Editor._empty)
					{
						// on an empty editor we want to completely replace
						// otherwise the first paragraph gets munged
						Editor.$editor.html($paster.html());
					}
					else
					{
						Editor.Commands.insertHTML($paster.html());
					}

					$paster = $paster.remove();
					finalize();
				}, _pollTime);

				return false;
			};
		}
	};
})();

// ---------------------------------------------------------------------

/**
 * Key Helper
 *
 * Small utility that holds key values and common shortcuts.
 */

// ---------------------------------------------------------------------

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


// ---------------------------------------------------------------------

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

// ---------------------------------------------------------------------

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

		this.Editor.updateField();
		this.Editor.selectEmptyParagraph();

		this.Undo.push(
			before.html, after.html,
			before.selection, after.selection
		);
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
		}
	},

	/**
	 * Save the state of the text they have
	 * typed so far.
	 *
	 * @todo call periodically to make it more natural?
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
			'cut undo redo paste input contextmenu': $.proxy(this._menuEvent, this)
		//	'click doubleclick mousedown mouseup': $.proxy(this._mouseEvent, this)
		};

		this.$editor.on(event_map);
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


// ---------------------------------------------------------------------

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

// ---------------------------------------------------------------------

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
			str1 = str1.substring(trim_before, l1 - trim_after);
			str2 = str2.substring(trim_before, l2 - trim_after);

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


// ---------------------------------------------------------------------

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

// ---------------------------------------------------------------------

WysiHat.Selection = function($el)
{
	this.$editor = $el;
	this.top = this.$editor.get(0);
}

WysiHat.Selection.prototype = {

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

		length = range.toString().replace(/\n/g, '').length;

		r.setStart(this.top, 0);
		r.setEnd(range.startContainer, range.startOffset);

		topOffset = r.toString().replace(/\n+/g, '').length;

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

		startOffset = this._getOffsetNode(this.top, start);
		r.setStart.apply(r, startOffset);

		// collapsed
		if (end === undefined)
		{
			end = start;
			r.collapse(true);
		}
		else
		{
			endOffset = this._getOffsetNode(this.top, end);
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
	_getOffsetNode: function(startNode, offset)
	{
		var curNode = startNode,
			curNodeLen = 0;

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
			// weird case where they try to select something from 0
			if (curNode.nodeType != Node.TEXT_NODE)
			{
				return [curNode, 0];
			}

			// Offset is 0 but we're at the end of the node,
			// jump ahead in the dom to the real beginning node.
			while (curNode.nextSibling === null)
			{
				curNode = curNode.parentNode;
			}

			curNode = curNode.nextSibling;
		}

		curNodeLen = curNode.nodeValue ? curNode.nodeValue.length : 0;
		return [curNode, curNodeLen + offset];
	}
};

WysiHat.Selection.constructor = WysiHat.Selection;


// ---------------------------------------------------------------------

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

// ---------------------------------------------------------------------

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
			'fontsize', 'forecolor', 'createLink', 'insertImage'
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
			'ol': 'orderedList',
			'ul': 'unorderedList',
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

		if ( ! a )
		{
			return false;
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
	 * This is a highly unsafe method to have lying
	 * around. It has to be called with apply to make
	 * sure it operates on the right scope. I honestly
	 * have no idea why it's written this way. -pk
	 *
	 * @todo fix it
	 */
	deleteElement: function()
	{
		var $this = $(this);
		$this.replaceWith( $this.html() );
	},

	/**
	 * Completely strips the editor of formatting.
	 * This is used primarly by the remove formatting
	 * button.
	 */
	stripFormattingElements: function()
	{
		function stripFormatters( i, el )
		{
			var $el = $(el);

			$el.children().each(stripFormatters);

			if ( isFormatter( $el ) )
			{
				deleteElement.apply( $el );
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
			$tools.hide();
			$editor.hide();
			$field.show();
		}
		else
		{
			$btn.find('b').text(text);
			$tools.show();
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
	},

	toggleList: function(type)
	{
		var
		$list = $('<'+type+'/>'),
		other = (type == 'ul') ? 'ol' : 'ul',
		that = this;

		if (this.is[type]())
		{
			this.manipulateSelection(function( range, $list ){
				this.getRangeElements( range, type ).each(function(i){
					var $this = $(this);
					$this.children('li').each(function(){
						var $this = $(this);
						that.replaceElement( $this, 'p' );
						$this.find('ol,ul').each(function(){
							var	$parent = $(this).parent();
							if ( $parent.is('p') )
							{
								that.deleteElement.apply( $parent );
							}
						});
					});
					that.deleteElement.apply( $this );
				});
			});
		}
		else
		{
			this.manipulateSelection(function( range, $list ){
				var $l = $list.clone();
				this.getRangeElements( range, this._blockElements ).each(function(i){
					var $this = $(this);
					if ( $this.parent().is(other) )
					{
						that.replaceElement( $this.parent(), type );
						$l = $this.parent();
					}
					else
					{
						if ( ! i )
						{
							$this.replaceWith( $l );
						}
						$this.appendTo( $l );
					}
				});
				$l.children(':not(li)').each(function(){
					that.replaceElement( $(this), 'li' );
				});
			}, $list );
		}
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

	orderedList: function() {
		WysiHat.Commands.toggleList('ol');
	},

	unorderedList: function() {
		WysiHat.Commands.toggleList('ul');
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


// ---------------------------------------------------------------------

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

// ---------------------------------------------------------------------

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
	},
};

$.extend(WysiHat.Editor.prototype, CommandsMixin);

// ---------------------------------------------------------------------

/**
 * Formatting Class
 *
 * Responsible for keeping the markup clean and compliant. Also
 * deals with keeping changes between the raw text and editor in
 * sync periodically.
 */

// ---------------------------------------------------------------------

WysiHat.Formatting = {
	cleanup: function($element)
	{
		var replaceElement = WysiHat.Commands.replaceElement;

		// kill comments
		$element.contents().filter(function() {
			return this.nodeType == Node.COMMENT_NODE;
		}).remove();

		$element
			.find('br')
				.replaceWith('\n')
				.end()
			.find('span')
				.each(function(){
					var $this = $(this);
					if ( $this.hasClass('Apple-style-span') )
					{
						$this.removeClass('Apple-style-span');
					}

					if ( $this.css('font-weight') == 'bold' &&
					 	 $this.css('font-style') == 'italic' )
					{
						$this.removeAttr('style').wrap('<strong>');
						replaceElement( $this, 'em' );
					}
					else if ( $this.css('font-weight') == 'bold' )
					{
						replaceElement( $this.removeAttr('style'), 'strong' );
					}
					else if ( $this.css('font-style') == 'italic' )
					{
						replaceElement( $this.removeAttr('style'), 'em' );
					}
				 })
				.end()
			.children('div')
				.each(function(){
				 	if ( ! this.attributes.length )
				 	{
				 		replaceElement( $(this), 'p' );
				 	}
				 })
				.end()
			.find('b')
				.each(function(){
				 	replaceElement($(this),'strong');
				 })
				.end()
			.find('i')
				.each(function(){
				 	replaceElement($(this),'em');
				 })
				.end()
			.find('strike')
				.each(function(){
				 	replaceElement($(this),'del');
				 })
				.end()
			.find('u')
				.each(function(){
				 	replaceElement($(this),'ins');
				 })
				.end()
			.find('p:empty,script,noscript,style').remove();
	},

	cleanupPaste: function($element, parentTagName)
	{
		var replaceElement = WysiHat.Commands.replaceElement;

		this.cleanup($element);

		// Ok, now we want to get rid of everything except for the
		// bare tags (with some exceptions, but not many). The trick
		// is to run through the found elements backwards. Otherwise
		// the node reference disappears when the parent is replaced.
		var els = $element.find('*'),
			rev = $.makeArray(els).reverse();

		$.each(rev, function() {
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

		// ok, now the fun bit with the paragraphs and newlines.
		// the browsers turn all newlines into paragraphs, we only
		// want them for the doubles newlines and brs otherwise. So
		// we need to step through all the sibling pairs and merge
		// when they are not separated by a blank.

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
	},

	reBlocks: new RegExp(
		'(<(?:ul|ol)>|<\/(?:' + WysiHat.Element.getBlocks().join('|') + ')>)[\r\n]*',
		'g'
	),

	format: function( $el )
	{
		$el.html(function(i, old) {
			return old.replace('<p>&nbsp;</p>', '')
				.replace(/<br\/?><\/p>/, '</p>')
				.replace(this.reBlocks, '$1\n')
				.replace(/\n+/, '\n')
				.replace(/<p>\n+<\/p>/, '');
		});
	},
	
	getBrowserMarkupFrom: function( $el )
	{
		var $container = $('<div>' + $el.val().replace(/\n/, '') + '</div>'),
			html;

		this.cleanup( $container );
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
				.replace( /<\/?[A-Z]+/g, function(tag){
					return tag.toLowerCase();
				 });
	}
};

// ---------------------------------------------------------------------

/**
 * Blank Button
 *
 * The base prototype for all buttons. Handles the basic init and
 * provides a nice way to extend the buttons without having to re-
 * do all of the work the toolbar does.
 */

// ---------------------------------------------------------------------

var BlankButton = {
	init: function(name, $editor)
	{
		this.name = name;
		this.$editor = $editor;
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

// ---------------------------------------------------------------------

/**
 * Toolbar Class
 *
 * Handles the creation of the toolbar and manages the individual
 * buttons states. You can add your own by using:
 * WysiHat.addButton(name, { options });
 */

// ---------------------------------------------------------------------

WysiHat.Toolbar = function($el, buttons)
{
	this.$editor = $el;
	this.$toolbar = $('<div class="' + WysiHat.name + '-editor-toolbar" role="presentation"></div>');

	$el.before(this.$toolbar);

	// add buttons
	var l = buttons.length, i;

	for (i = 0 ; i < l; i++)
	{
		this.addButton(buttons[i]);
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

			$btn = $('<select class="button picker"/>');

			for ( ; i < l; i++)
			{
				$btn.append(
					'<option value="' + opts[i][0] + '">' + opts[i][1] + '</option>'
				);
			}
		}
		else
		{
			$btn = $('<button aria-pressed="false" tabindex="-1"></button>');

			$btn.append('<b>' + button.label + '</b>')
				.addClass( 'button ' + button.name)
				.hover(
					function() {
						this.title = $(this).find('b').text();
					},
					function() {
						$(this).removeAttr('title');
					}
				);
		}

		$btn.appendTo(this.$toolbar);

		if (button.cssClass)
		{
			$btn.addClass(button.cssClass);
		}

		if (button.title)
		{
			$btn.attr('title', button.title);
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
		var evt = (button.type && button.type == 'select') ? 'change' : 'click';

		button.$element.on(evt, function(e){
			var $editor = button.$editor;

			// Bring focus to the editor before the handler is called
			// so that selection data is available to tools
			if ( ! $editor.is(':focus'))
			{
				$editor.focus();
			}

			button.Event.fire(button.name);

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

// ---------------------------------------------------------------------

/**
 * Defaults and jQuery Binding
 *
 * This code sets up reasonable editor defaults and then adds
 * a convenience setup function to jQuery.fn that you can use
 * as $('textarea').wysihat(options).
 */

// ---------------------------------------------------------------------

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
		WysiHat.attach(this, options);
	});
};



// ---------------------------------------------------------------------

/**
 * Browser Compat Classes
 *
 * Below we normalize the Range and Selection classes to work
 * properly across all browsers. If you like IE, you'll feel
 * right at home down here.
 */

// ---------------------------------------------------------------------


(function(document, $) {

/*  IE Selection and Range classes
 *
 *  Original created by Tim Cameron Ryan
 *	http://github.com/timcameronryan/IERange
 *  Copyright (c) 2009 Tim Cameron Ryan
 *  Released under the MIT/X License
 *
 *  Modified by Joshua Peek
 */
if (!window.getSelection) {
	(function($){
		var DOMUtils = {
			isDataNode: function( node )
			{
				try {
					return node && node.nodeValue !== null && node.data !== null;
				} catch (e) {
					return false;
				}
			},

			isAncestorOf: function( parent, node )
			{
				if ( ! parent )
				{
					return false;
				}
				return ! DOMUtils.isDataNode(parent) &&
					   ( node.parentNode == parent ||
						 parent.contains( DOMUtils.isDataNode(node) ? node.parentNode : node ) );
			},

			isAncestorOrSelf: function( root, node )
			{
				return root == node ||
				 	   DOMUtils.isAncestorOf( root, node );
			},

			findClosestAncestor: function( root, node )
			{
				if ( DOMUtils.isAncestorOf( root, node ) )
				{
					while ( node && node.parentNode != root )
					{
						node = node.parentNode;
					}
				}
				return node;
			},

			getNodeLength: function(node)
			{
				return DOMUtils.isDataNode(node) ? node.length : node.childNodes.length;
			},

			splitDataNode: function( node, offset )
			{
				if ( ! DOMUtils.isDataNode( node ) )
				{
					return false;
				}
				var newNode = node.cloneNode(false);
				node.deleteData(offset, node.length);
				newNode.deleteData(0, offset);
				node.parentNode.insertBefore( newNode, node.nextSibling );
			}
		};

		window.Range = (function(){

			function Range( document )
			{
				this._document = document;
				this.startContainer = this.endContainer = document.body;
				this.endOffset = DOMUtils.getNodeLength(document.body);
			}

			function findChildPosition( node )
			{
				for ( var i = 0; node = node.previousSibling; i++ )
				{
					continue;
				}
				return i;
			}

			Range.prototype = {

				START_TO_START:	0,
				START_TO_END:	1,
				END_TO_END:		2,
				END_TO_START:	3,

				startContainer:	null,
				startOffset:	0,
				endContainer:	null,
				endOffset:		0,
				commonAncestorContainer: null,
				collapsed:		false,
				_document:		null,

				_toTextRange: function()
				{
					function adoptEndPoint( textRange, domRange, bStart )
					{
						var
						container		= domRange[bStart ? 'startContainer' : 'endContainer'],
						offset			= domRange[bStart ? 'startOffset' : 'endOffset'],
						textOffset		= 0,
						anchorNode		= DOMUtils.isDataNode(container) ? container : container.childNodes[offset],
						anchorParent	= DOMUtils.isDataNode(container) ? container.parentNode : container,
						cursorNode		= domRange._document.createElement('a'),
						cursor			= domRange._document.body.createTextRange();

						if ( container.nodeType == 3 ||
							 container.nodeType == 4 )
						{
							textOffset = offset;
						}

						textRange.setEndPoint(bStart ? 'StartToStart' : 'EndToStart', cursor);
						textRange[bStart ? 'moveStart' : 'moveEnd']('character', textOffset);
					}

					var textRange = this._document.body.createTextRange();
					adoptEndPoint(textRange, this, true);
					adoptEndPoint(textRange, this, false);
					return textRange;
				},

				_refreshProperties: function()
				{
					this.collapsed = (this.startContainer == this.endContainer && this.startOffset == this.endOffset);
					var node = this.startContainer;
					while ( node &&
							node != this.endContainer &&
							! DOMUtils.isAncestorOf(node, this.endContainer) )
					{
						node = node.parentNode;
					}
					this.commonAncestorContainer = node;
				},

				setStart: function( container, offset )
				{
					this.startContainer	= container;
					this.startOffset	= offset;
					this._refreshProperties();
				},

				setEnd: function( container, offset )
				{
					this.endContainer	= container;
					this.endOffset		= offset;
					this._refreshProperties();
				},

				setStartBefore: function( refNode )
				{
					this.setStart( refNode.parentNode, findChildPosition(refNode) );
				},

				setStartAfter: function(refNode)
				{
					this.setStart( refNode.parentNode, findChildPosition(refNode) + 1 );
				},

				setEndBefore: function( refNode )
				{
					this.setEnd(refNode.parentNode, findChildPosition(refNode));
				},

				setEndAfter: function( refNode )
				{
					this.setEnd( refNode.parentNode, findChildPosition(refNode) + 1 );
				},

				selectNode: function( refNode )
				{
					this.setStartBefore(refNode);
					this.setEndAfter(refNode);
				},

				selectNodeContents: function( refNode )
				{
					this.setStart(refNode, 0);
					this.setEnd(refNode, DOMUtils.getNodeLength(refNode));
				},

				collapse: function(toStart)
				{
					if (toStart)
					{
						this.setEnd(this.startContainer, this.startOffset);
					}
					else
					{
						this.setStart(this.endContainer, this.endOffset);
					}
				},

				cloneContents: function()
				{
					return (function cloneSubtree( iterator ){
						for ( var node, frag = document.createDocumentFragment(); node = iterator.next(); )
						{
							node = node.cloneNode( ! iterator.hasPartialSubtree() );
							if ( iterator.hasPartialSubtree() )
							{
								node.appendChild( cloneSubtree( iterator.getSubtreeIterator() ) );
							}
							frag.appendChild( node );
						}
						return frag;
					})( new RangeIterator(this) );
				},

				extractContents: function()
				{
					var range = this.cloneRange();
					if (this.startContainer != this.commonAncestorContainer)
					{
						this.setStartAfter(DOMUtils.findClosestAncestor(this.commonAncestorContainer, this.startContainer));
					}
					this.collapse(true);
					return (function extractSubtree( iterator ){
						for ( var node, frag = document.createDocumentFragment(); node = iterator.next(); )
						{
							iterator.hasPartialSubtree() ? node = node.cloneNode(false) : iterator.remove();
							if ( iterator.hasPartialSubtree() )
							{
								node.appendChild( extractSubtree( iterator.getSubtreeIterator() ) );
							}
							frag.appendChild( node );
						}
						return frag;
					})( new RangeIterator(range) );
				},

				deleteContents: function()
				{
					var range = this.cloneRange();
					if (this.startContainer != this.commonAncestorContainer)
					{
						this.setStartAfter( DOMUtils.findClosestAncestor( this.commonAncestorContainer, this.startContainer ) );
					}
					this.collapse(true);
					(function deleteSubtree( iterator ){
						while ( iterator.next() )
						{
							iterator.hasPartialSubtree() ? deleteSubtree( iterator.getSubtreeIterator() ) : iterator.remove();
						}
					})( new RangeIterator(range) );
				},

				insertNode: function(newNode)
				{
					if (DOMUtils.isDataNode(this.startContainer))
					{
						DOMUtils.splitDataNode( this.startContainer, this.startOffset );
						this.startContainer.parentNode.insertBefore( newNode, this.startContainer.nextSibling );
					}
					else
					{
						var offsetNode = this.startContainer.childNodes[this.startOffset];
						if (offsetNode)
						{
							this.startContainer.insertBefore( newNode, offsetNode );
						}
						else
						{
							this.startContainer.appendChild( newNode );
						}
					}
					this.setStart(this.startContainer, this.startOffset);
				},

				surroundContents: function(newNode)
				{
					var content = this.extractContents();
					this.insertNode(newNode);
					newNode.appendChild(content);
					this.selectNode(newNode);
				},

				compareBoundaryPoints: function(how, sourceRange)
				{
					var containerA, offsetA, containerB, offsetB;
					switch ( how )
					{
						case this.START_TO_START:
						case this.START_TO_END:
							containerA = this.startContainer;
							offsetA = this.startOffset;
							break;
						case this.END_TO_END:
						case this.END_TO_START:
							containerA = this.endContainer;
							offsetA = this.endOffset;
							break;
					}
					switch ( how )
					{
						case this.START_TO_START:
						case this.END_TO_START:
							containerB = sourceRange.startContainer;
							offsetB = sourceRange.startOffset;
							break;
						case this.START_TO_END:
						case this.END_TO_END:
							containerB = sourceRange.endContainer;
							offsetB = sourceRange.endOffset;
							break;
					}

					return ( containerA.sourceIndex < containerB.sourceIndex
								? -1
								: ( containerA.sourceIndex == containerB.sourceIndex
										? ( offsetA < offsetB
												? -1
												: ( offsetA == offsetB ? 0 : 1 )
										  ) // offsetA < offsetB
										: 1
								  ) // containerA.sourceIndex == containerB.sourceIndex
						   ); // containerA.sourceIndex < containerB.sourceIndex
				},

				cloneRange: function()
				{
					var range = new Range( this._document );
					range.setStart( this.startContainer, this.startOffset );
					range.setEnd( this.endContainer, this.endOffset );
					return range;
				},

				toString: function()
				{
					return this._toTextRange().text;
				},

				createContextualFragment: function( tagString )
				{
					var
					content		= ( DOMUtils.isDataNode(this.startContainer) ? this.startContainer.parentNode
																			 : this.startContainer ).cloneNode(false),
					fragment	= this._document.createDocumentFragment();

					content.innerHTML = tagString;
					for ( ; content.firstChild; )
					{
						fragment.appendChild(content.firstChild);
					}
					return fragment;
				}
			};

			function RangeIterator(range)
			{
				this.range = range;
				if ( range.collapsed )
				{
					return;
				}

				var root	= range.commonAncestorContainer;
				this._next	= range.startContainer == root && ! DOMUtils.isDataNode( range.startContainer )
								? range.startContainer.childNodes[range.startOffset]
								: DOMUtils.findClosestAncestor( root, range.startContainer );
				this._end	= range.endContainer == root && ! DOMUtils.isDataNode( range.endContainer )
								? range.endContainer.childNodes[range.endOffset]
								: DOMUtils.findClosestAncestor( root, range.endContainer ).nextSibling;
			}

			RangeIterator.prototype = {

				range: null,
				_current: null,
				_next: null,
				_end: null,

				hasNext: function()
				{
					return !! this._next;
				},

				next: function()
				{
					var current	= this._current = this._next;
					this._next	= this._current && this._current.nextSibling != this._end ? this._current.nextSibling : null;

					if (DOMUtils.isDataNode(this._current))
					{
						if ( this.range.endContainer == this._current )
						{
							( current = current.cloneNode(true) ).deleteData( this.range.endOffset, current.length - this.range.endOffset );
						}
						if ( this.range.startContainer == this._current )
						{
							( current = current.cloneNode(true) ).deleteData( 0, this.range.startOffset );
						}
					}
					return current;
				},

				remove: function()
				{
					if ( DOMUtils.isDataNode(this._current) &&
						 ( this.range.startContainer == this._current ||
						   this.range.endContainer == this._current ) )
					{
						var
						start	= this.range.startContainer == this._current ? this.range.startOffset : 0,
						end		= this.range.endContainer == this._current ? this.range.endOffset : this._current.length;
						this._current.deleteData( start, end - start );
					}
					else
					{
						this._current.parentNode.removeChild( this._current );
					}
				},

				hasPartialSubtree: function()
				{
					return ! DOMUtils.isDataNode(this._current) &&
						   ( DOMUtils.isAncestorOrSelf( this._current, this.range.startContainer ) ||
							 DOMUtils.isAncestorOrSelf( this._current, this.range.endContainer ) );
				},

				getSubtreeIterator: function()
				{
					var subRange = new Range(this.range._document);
					subRange.selectNodeContents(this._current);
					if ( DOMUtils.isAncestorOrSelf(this._current, this.range.startContainer) )
					{
						subRange.setStart( this.range.startContainer, this.range.startOffset );
					}
					if ( DOMUtils.isAncestorOrSelf( this._current, this.range.endContainer ) )
					{
						subRange.setEnd(this.range.endContainer, this.range.endOffset);
					}
					return new RangeIterator(subRange);
				}
			};

			return Range;
		})();

		window.Range._fromTextRange = function( textRange, document )
		{
			function adoptBoundary(domRange, textRange, bStart)
			{
				var
				cursorNode	= document.createElement('a'),
				cursor		= textRange.duplicate(),
				parent;

				cursor.collapse(bStart);
				parent = cursor.parentElement();

				do {
					parent.insertBefore( cursorNode, cursorNode.previousSibling );
					cursor.moveToElementText( cursorNode );
				} while ( cursorNode.previousSibling &&
						  cursor.compareEndPoints( bStart ? 'StartToStart' : 'StartToEnd', textRange ) > 0 );

				if ( cursorNode.nextSibling &&
					 cursor.compareEndPoints(bStart ? 'StartToStart' : 'StartToEnd', textRange) == -1 )
				{
					cursor.setEndPoint( bStart ? 'EndToStart' : 'EndToEnd', textRange );
					domRange[bStart ? 'setStart' : 'setEnd']( cursorNode.nextSibling, cursor.text.length );
				}
				else
				{
					domRange[bStart ? 'setStartBefore' : 'setEndBefore'](cursorNode);
				}
				cursorNode.parentNode.removeChild(cursorNode);
			}

			var domRange = new Range(document);
			adoptBoundary(domRange, textRange, true);
			adoptBoundary(domRange, textRange, false);
			return domRange;
		};

		document.createRange = function()
		{
			return new Range(document);
		};

		window.Selection = (function(){
			function Selection(document)
			{
				this._document = document;

				var selection = this;
				document.attachEvent('onselectionchange', function(){
					selection._selectionChangeHandler();
				});

				setTimeout(function(){
					selection._selectionChangeHandler();
				},10);
			}

			Selection.prototype = {

				rangeCount: 0,
				_document:	null,
				anchorNode:	null,
				focusNode:	null,

				_selectionChangeHandler: function()
				{
					var
					range	= this._document.selection.createRange(),
					text	= range.text.split(/\r|\n/),
					$parent	= $( range.parentElement() ),
					anchorRe, focusRe;

					if ( text.length > 1 )
					{
						anchorRe = new RegExp( text[0] + '$' );
						focusRe = new RegExp( '^' + text[text.length-1] );

						$parent.children().each(function(){
							if ( $(this).text().match( anchorRe ) )
							{
								this.anchorNode = this;
							}
							if ( $(this).text().match( focusRe ) )
							{
								this.focusNode = this;
							}
						});
					}
					else
					{
						this.anchorNode = $parent.get(0);
						this.focusNode	= this.anchorNode;
					}

					this.rangeCount = this._selectionExists( range ) ? 1 : 0;
				},

				_selectionExists: function( textRange )
				{
					return textRange.parentElement().isContentEditable ||
						   textRange.compareEndPoints('StartToEnd', textRange) != 0;
				},
				addRange: function(range)
				{
					var
					selection	= this._document.selection.createRange(),
					textRange	= range._toTextRange();
					if ( ! this._selectionExists(selection) )
					{
						try {
 							textRange.select();
						} catch(e) {}
					}
					else
					{
						if (textRange.compareEndPoints('StartToStart', selection) == -1)
						{
							if ( textRange.compareEndPoints('StartToEnd', selection) > -1 &&
								 textRange.compareEndPoints('EndToEnd', selection) == -1 )
							{
								selection.setEndPoint('StartToStart', textRange);
							}
						}
						else
						{
							if ( textRange.compareEndPoints('EndToStart', selection) < 1 &&
								 textRange.compareEndPoints('EndToEnd', selection) > -1 )
							{
								selection.setEndPoint('EndToEnd', textRange);
							}
						}
						selection.select();
					}
				},
				removeAllRanges: function()
				{
					this._document.selection.empty();
				},
				getRangeAt: function(index)
				{
					var textRange = this._document.selection.createRange();
					if ( this._selectionExists( textRange ) )
					{
						return Range._fromTextRange( textRange, this._document );
					}
					return null;
				},
				toString: function()
				{
					return this._document.selection.createRange().text;
				},
				isCollapsed: function()
				{
					var range = document.createRange();
					return range.collapsed;
				},
				deleteFromDocument: function()
				{
					var textRange = this._document.selection.createRange();
					textRange.pasteHTML('');
				}
			};

			return Selection;
		})();

		window.getSelection = (function(){
			var selection = new Selection(document);
			return function() { return selection; };
		})();

	})(jQuery);
}



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



$.extend(Range.prototype, {

	beforeRange: function(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ( this.compareBoundaryPoints( this.START_TO_START, range ) == -1 &&
				 this.compareBoundaryPoints( this.START_TO_END, range ) == -1 &&
				 this.compareBoundaryPoints( this.END_TO_END, range ) == -1 &&
				 this.compareBoundaryPoints( this.END_TO_START, range ) == -1 );
	},

	afterRange: function(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ( this.compareBoundaryPoints( this.START_TO_START, range ) == 1 &&
				 this.compareBoundaryPoints( this.START_TO_END, range ) == 1 &&
				 this.compareBoundaryPoints( this.END_TO_END, range ) == 1 &&
				 this.compareBoundaryPoints( this.END_TO_START, range ) == 1 );
	},

	betweenRange: function(range)
	{
		if ( ! range ||
			 ! range.compareBoundaryPoints )
		{
			return false;
		}
		return ! ( this.beforeRange(range) || this.afterRange(range) );
	},

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
	},

	getNode: function()
	{
		var
		parent	= this.commonAncestorContainer,
		that	= this,
		child;

		while (parent.nodeType == Node.TEXT_NODE)
		{
			parent = parent.parentNode;
		}

		$(parent).children().each(function(){
			var range = document.createRange();
			range.selectNodeContents(this);
			child = that.betweenRange(range);
		});

		return $(child || parent).get(0);
	}
});

if ( typeof Selection == 'undefined' )
{
	var Selection = {};
	Selection.prototype = window.getSelection().__proto__;
}

// functions we want to normalize
var
getNode,
selectNode,
setBookmark,
moveToBookmark;

if ( $.browser.msie )
{
	getNode = function()
	{
		var range = this._document.selection.createRange();
		return $(range.parentElement());
	}

	selectNode = function(element)
	{
		var range = this._document.body.createTextRange();
		range.moveToElementText(element);
		range.select();
	}

	setBookmark = function()
	{
		var
		$bookmark	= $('#WysiHat-bookmark'),
		$parent		= $('<div/>'),
		range		= this._document.selection.createRange();

		if ( $bookmark.length > 0 )
		{
			$bookmark.remove();
		}

		$bookmark = $( '<span id="WysiHat-bookmark">&nbsp;</span>' )
						.appendTo( $parent );

		range.collapse(true);
		range.pasteHTML( $parent.html() );
	}

	moveToBookmark = function()
	{
		var
		$bookmark	= $('#WysiHat-bookmark'),
		range		= this._document.selection.createRange();

		if ( $bookmark.length > 0 )
		{
			$bookmark.remove();
		}

		range.moveToElementText( $bookmark.get(0) );
		range.collapse(true);
		range.select();

		$bookmark.remove();
	}
}
else
{
	getNode = function()
	{
		return ( this.rangeCount > 0 ) ? this.getRangeAt(0).getNode() : null;
	}

	selectNode = function(element)
	{
		var range = document.createRange();
		range.selectNode(element[0]);
		this.removeAllRanges();
		this.addRange(range);
	}

	setBookmark = function()
	{
		var $bookmark	= $('#WysiHat-bookmark');

		if ( $bookmark.length > 0 )
		{
			$bookmark.remove();
		}

		$bookmark = $( '<span id="WysiHat-bookmark">&nbsp;</span>' );

		this.getRangeAt(0).insertNode( $bookmark.get(0) );
	}

	moveToBookmark = function()
	{
		var
		$bookmark	= $('#WysiHat-bookmark'),
		range		= document.createRange();

		if ( $bookmark.length > 0 )
		{
			$bookmark.remove();
		}

		range.setStartBefore( $bookmark.get(0) );
		this.removeAllRanges();
		this.addRange(range);

		$bookmark.remove();
	}
}

$.extend(Selection.prototype, {
	getNode: getNode,
	selectNode: selectNode,
	setBookmark: setBookmark,
	moveToBookmark: moveToBookmark
});


})(document, jQuery);