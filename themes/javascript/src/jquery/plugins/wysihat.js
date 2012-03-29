/*  WysiHat - WYSIWYG JavaScript framework, version 0.2.1
 *  (c) 2008-2010 Joshua Peek
 *  JQ-WysiHat - jQuery port of WysiHat to run on jQuery
 *  (c) 2010 Scott Williams & Aaron Gustafson
 *
 *  WysiHat is freely distributable under the terms of an MIT-style license.
 *--------------------------------------------------------------------------*/


// ---------------------------------------------------------------------

/**
 * This file is rather lengthy, so I've organized it into rough
 * sections. I suggest reading the documentation for each section
 * to get a general idea of where things happen. The list below
 * are headers (except for #1) so that you can search for them.
 *
 * WysiHat Base
 * Element Manager
 * Change Events
 * Editor Commands
 * Paste Handler
 * Formatting Class
 * Toolbar Class
 * Defaults and jQuery Binding
 * Browser Compat Classes
 */

// ---------------------------------------------------------------------


var WysiHat = {
	name:	'WysiHat'
};

(function($){

	var
	WYSIHAT 	= WysiHat.name,
	EDITOR		= '-editor',
	FIELD		= '-field',
	CHANGE		= ':change',
	CLASS		= WYSIHAT + EDITOR,
	E_EVT		= CLASS + CHANGE,
	F_EVT		= WYSIHAT + FIELD + CHANGE,
	IMMEDIATE	= ':immediate',
	INDEX		= 0;

	WysiHat.Editor = {

		attach: function( $field )
		{
			var
			tId	= $field.attr( 'id' ),
			eId	= ( tId ? tId : WYSIHAT + INDEX++ ) + EDITOR,
			fTimer	= null,
			eTimer	= null,
			$editor	= $( '#' + eId );

			if ( tId == '' )
			{
				tId = eId.replace( EDITOR, FIELD );
				$field.attr( 'id', tId );
			}

			if ( $editor.length )
			{
				if ( ! $editor.hasClass( CLASS ) )
				{
					$editor.addClass( CLASS );
				}
				return $editor;
			}

			$editor = $('<div id="' + eId + '" class="' + CLASS + '" contentEditable="true" role="application"></div>')
				.html( WysiHat.Formatting.getBrowserMarkupFrom( $field ) )
				.data( 'field', $field );

			$.extend( $editor, WysiHat.Commands );

			$editor.selectionUtil = new WysiHat.SelectionUtil($editor);
			$editor.data('selectionUtil', $editor.selectionUtil);

			$editor.eventCore = new WysiHat.EventCore($editor);
			$editor.data('eventCore', $editor.eventCore);

			function updateField()
			{
				$field.val( WysiHat.Formatting.getApplicationMarkupFrom( $editor ) );
				fTimer = null;
			}
			function updateEditor()
			{
				$editor.html( WysiHat.Formatting.getBrowserMarkupFrom( $field ) );
				eTimer = null;
			}

			$field
				.data( 'editor', $editor )
				.bind('keyup mouseup',function(){
					$field.trigger(F_EVT);
				 })
				.bind( F_EVT, function(){
					if ( fTimer )
					{
						clearTimeout( fTimer );
					}
					fTimer = setTimeout(updateEditor, 250 );
				 })
				.bind( F_EVT + IMMEDIATE, updateEditor)
				.hide()
				.before(
					$editor
						.bind('keyup mouseup',function(){
							$editor.trigger(E_EVT);
						 })
						.bind( E_EVT, function(){
							if ( eTimer )
							{
								clearTimeout( eTimer );
							}
							eTimer = setTimeout(updateField, 250);
						 })
						.bind( E_EVT + IMMEDIATE, updateField )
				 )

			return $editor;
		}
	};

})(jQuery);


// ---------------------------------------------------------------------

/**
 * Element Manager
 *
 * Holds information about available elements and can be used to
 * check if an element is of a valid type.
 */

// ---------------------------------------------------------------------

WysiHat.Element = (function( $ ){

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

})( jQuery );


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

(function($, DOC){

	$(document).ready(function(){

		var
		timer = null,
		$doc = $(DOC),
		// &#x200b; is a zero-width character so we have something to select
		// and place the cursor inside the paragraph tags, Webkit won't select
		// an empty element due to a long-standing bug
		// https://bugs.webkit.org/show_bug.cgi?id=15256
		// <wbr> didn't seem to behave the same so I'm using the entity
		// http://www.quirksmode.org/oddsandends/wbr.html
		empty	= '<p>&#x200b;</p>',
		previousRange,
		selectionChangeHandler,
		$element;

		function fieldChangeHandler( e )
		{
			if ( timer )
			{
				clearTimeout(timer);
			}

			$element = $(this);

			timer = setTimeout(function(){
				var
				element		= $element.get(0),
				val, evt;

				if ( $element.is('*[contenteditable=""],*[contenteditable=true]') )
				{
					val	= $element.html();

					if ( val == '' ||
					 	 val == '<br>' ||
					 	 val == '<br/>' )
					{
						val = empty;
						$element.html(val);
						selectEmptyParagraph($element);
					}

					evt	= 'editor:change';
				}
				else
				{
					val	= $element.val();
					evt	= 'field:change';
				}

				if ( val &&
					 element.previousValue != val )
				{
					$element.trigger( 'WysiHat-' + evt );
					element.previousValue = val;
				}
			}, 100);
		}

		function selectEmptyParagraph( $el )
		{
			var $el	= $element || $(this),
				s	= window.getSelection(),
				r	= document.createRange();
			// If the editor has our special zero-width character in it wrapped
			// with paragraph tags, select it
			if ( $el.html() == '<p>​</p>' )
			{
				s.removeAllRanges();
				r.selectNodeContents($el.find('p').get(0));
				s.addRange(r);
				
				// Get Firefox's cursor behaving naturally by clearing out the
				// zero-width character; if we run this for webkit too, then it
				// breaks Webkit's cursor behavior
				if ($.browser.mozilla)
				{
					$el.find('p').eq(0).html('');
				}
			}
		}

		$('body')
			.delegate('input,textarea,*[contenteditable],*[contenteditable=true]', 'keydown', fieldChangeHandler )
			.delegate('*[contenteditable],*[contenteditable=true]', 'focus', selectEmptyParagraph );


		if ( 'onselectionchange' in DOC &&
			 'selection' in DOC )
		{
			selectionChangeHandler = function()
			{
				var
				range	= DOC.selection.createRange(),
				element	= range.parentElement();
				$(element)
					.trigger( 'WysiHat-selection:change' );
			}

	 		$doc.bind( 'selectionchange', selectionChangeHandler );
		}
		else
		{
			selectionChangeHandler = function()
			{
				var
				element		= DOC.activeElement,
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

})(jQuery, document);



///////////////////////////////////
// BRAND NEW EVENT SYSTEM! WOOT! //
///////////////////////////////////

(function($){

	var
	KEYS,
	keyShortcuts;

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
			'underline': prefix + '-u',
		};
	})();

	WysiHat.EventCore = function($el)
	{
		this.$editor = $el;
		this.observers = {};

		this.textStart = null;
		this.textStartSel = null;
		this.textDeleting = false; // typing backwards ;)

		// helper classes
		this.undoStack = new WysiHat.UndoStack();
		this.selectionUtil = $el.data('selectionUtil');

		// @todo implement all out hijacking
		this._hijack_events();
	}

	WysiHat.EventCore.prototype = {

		/**
		 * Bind an event observer
		 *
		 * $editor.observer('bold', { ... })
		 */
		observe: function(name, options)
		{
			// @todo if options is a function, pick one?
			// @todo monkey patch .bind on the editor?

			if ( ! this.observers[name])
			{
				this.observers = [];
			}

			this.observers.push(options);
		},

		/**
		 * Fire an event on all observers
		 *
		 * $editor.fire('bold')
		 */
		fire: function(name)
		{
			// mark text change
			this._saveTextState(name);

			if (name == 'undo' || name == 'redo')
			{
				var modified,
					check = (name == 'undo') ? 'hasUndo' : 'hasRedo';

				if (this.undoStack[check]())
				{
					modified = this.undoStack[name](this.$editor.html());

					this.$editor.html(modified[0]);
					this.selectionUtil.set(modified[1]);
				}
			}

			return;

			// @pk Working on it, don't worry!

			var
			i,
			ret,
			length,
			beforeRange,
			beforeContent,
			changedContent,
			afterRange,
			afterContent;

			if ( ! this.observers[name] || ! this.observers[name].length)
			{
				return;
			}

			length = observers.length;

			// @todo undo stack
			// @todo grab string and range

			// run the before events
			for (i = 0; i < length; i++)
			{
				if (observers[i].before)
				{
					ret = observers[i].before(beforeContent, beforeRange);

					// event was canceled
					if (ret === false)
					{
						return;
					}
				}
			}

			changedContent = beforeContent;

			// run the during events
			for (i = 0; i < length; i++)
			{
				if (observers[i].during)
				{
					ret = observers[i].during(changedContent, beforeContent);

					if (ret !== undefined)
					{
						changedContent = ret;
					}
				}
			}

			afterContent = changedContent;
			// @todo grab new range

			// run the after events
			for (i = 0; i < length; i++)
			{
				if (observers[i].after)
				{
					observers[i].after(afterContent, afterRange);
				}
			}

			// @todo undo stack
		},

		textChange: function(before, after, selBefore, selAfter)
		{
			this.undoStack.push(before, after, selBefore, selAfter);
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
		 * Save the state of the text they have
		 * typed so far.
		 *
		 * @todo call periodically to make it more natural
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
				var selEnd = this.selectionUtil.get();
				this.textChange(this.textStart, this.$editor.html(), this.textStartSel, selEnd);
				this.textStart = null;
				this.textStartSel = null;
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

			//	'blur change': $.proxy(this._blurEvent, this),
				'selectionchange focus mouseup': $.proxy(this._rangeEvent, this),
				'keydown keyup keypress': $.proxy(this._keyEvent, this),
			//	'cut input paste': $.proxy(this._event, this),
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
			var attempt = ['undo', 'redo'],
				name;

			if (evt.type == 'keydown')
			{
				while(name = attempt.shift())
				{
					if (this.isEvent(name, evt))
					{
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
			var s;

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
					this.textStartSel = this.selectionUtil.get();
					this.textStart = this.$editor.html();
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
		}
	};

	WysiHat.EventCore.constructor = WysiHat.EventCore;



	WysiHat.UndoStack = function()
	{
		// @todo max depth
		this.saved = [],
		this.index = 0;
	}

	WysiHat.UndoStack.prototype = {

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
				if (this.index < this.saved.length)
				{
					this.saved = this.saved.slice(0, this.index);
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

			// It involved walking through the whole thing? Ignore it
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

	WysiHat.UndoStack.constructor = WysiHat.UndoStack;


	WysiHat.SelectionUtil = function($el)
	{
		this.$editor = $el;
		this.top = this.$editor.get(0);
	}

	WysiHat.SelectionUtil.prototype = {

		/**
		 * Get current selection offsets based on
		 * the editors *text* (not html!).
		 */
		get: function(range)
		{
			var s = window.getSelection(),
				r = document.createRange(),
				length, topOffset;

			if (range === undefined)
			{
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
		 */
		_getOffsetNode: function(startNode, offset)
		{
			var curNode = startNode,
				curNodeLen = 0;

			function getTextNodes(node)
			{
				if (node.nodeType == 3 || node.nodeType == 4)
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

			// weird case where they try to select something from 0
			if (curNode.nodeType != 3 && offset == 0)
			{
				return [curNode, 0];
			}

			return [curNode, curNode.nodeValue.length + offset];
		}
	};

	WysiHat.SelectionUtil.constructor = WysiHat.SelectionUtil;

})(jQuery);

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

WysiHat.Commands = (function( WIN, DOC, $ ){

	var
	WYSIHAT_EDITOR	= 'WysiHat-editor',
	CHANGE_EVT		= WYSIHAT_EDITOR + ':change',

	validCommands	= [ 'backColor', 'bold', 'createLink', 'fontName', 'fontSize', 'foreColor', 'hiliteColor',
						'italic', 'removeFormat', 'strikethrough', 'subscript', 'superscript', 'underline', 'unlink',
						'delete', 'formatBlock', 'forwardDelete', 'indent', 'insertHorizontalRule', 'insertHTML',
						'insertImage', 'insertLineBreak', 'insertOrderedList', 'insertParagraph', 'insertText',
						'insertUnorderedList', 'justifyCenter', 'justifyFull', 'justifyLeft', 'justifyRight', 'outdent',
						'copy', 'cut', 'paste', 'selectAll', 'styleWithCSS', 'useCSS' ],

	blockElements	= WysiHat.Element.getContentElements().join(',').replace( ',div,', ',div:not(.' + WYSIHAT_EDITOR + '),' );

	function boldSelection()
	{
		this.execCommand('bold', false, null);
	}
	function isBold()
	{
		return ( selectionIsWithin('b,strong') || document.queryCommandState('bold') );
	}
	function underlineSelection()
	{
		this.execCommand('underline', false, null);
	}
	function isUnderlined()
	{
		return ( selectionIsWithin('u,ins') || document.queryCommandState('underline') );
	}
	function italicizeSelection()
	{
		this.execCommand('italic', false, null);
	}
	function isItalic()
	{
		return ( selectionIsWithin('i,em') || document.queryCommandState('italic') );
	}
	function strikethroughSelection()
	{
		this.execCommand('strikethrough', false, null);
	}
	function isStruckthrough()
	{
		return ( selectionIsWithin('s,del') || document.queryCommandState('strikethrough') );
	}

	function quoteSelection()
	{
		var $quote = $('<blockquote/>');
		this.manipulateSelection(function( range, $quote ){
			var
			$q		= $quote.clone(),
			$els	= this.getRangeElements( range, blockElements ),
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
	}

	function unquoteSelection()
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
	function toggleIndentation()
	{
		if ( this.isIndented() )
		{
			this.unquoteSelection();
		}
		else
		{
			this.quoteSelection();
		}
	}
	function isIndented()
	{
		return selectionIsWithin('blockquote');
	}


	function fontSelection(font)
	{
		this.execCommand('fontname', false, font);
	}
	function fontSizeSelection(fontSize)
	{
		this.execCommand('fontsize', false, fontSize);
	}
	function colorSelection(color)
	{
		this.execCommand('forecolor', false, color);
	}
	function backgroundColorSelection(color)
	{
		if ( $.browser.mozilla )
		{
			this.execCommand('hilitecolor', false, color);
		}
		else
		{
			this.execCommand('backcolor', false, color);
		}
	}


	function alignSelection(alignment)
	{
		this.execCommand('justify' + alignment);
	}
	function alignSelected()
	{
		var node = WIN.getSelection().getNode();
		return $(node).css('textAlign');
	}


	function linkSelection(url)
	{
		this.execCommand('createLink', false, url);
	}
	function unlinkSelection()
	{
		this.manipulateSelection(function( range ){
			this.getRangeElements( range, '[href]' ).each(this.clearElement);
		});
	}
	function isLinked()
	{
		return selectionIsWithin('a[href]');
	}


	function toggleOrderedList()
	{
		var
		$list = $('<ol/>');

		if ( isOrderedList() )
		{
			this.manipulateSelection(function( range, $list ) {
				this.getRangeElements( range, 'ol' ).each(function(i) {
					var $this = $(this);
					$this.children('li').each(function(){
						var $this = $(this);
						replaceElement( $this, 'p' );
						$this.find('ol,ul').each(function() {
							var	$parent = $(this).parent();
							if ( $parent.is('p') )
							{
								deleteElement.apply( $parent );
							}
						});
					});
					deleteElement.apply( $this );
				});
			});
		}
		else
		{
			this.manipulateSelection(function( range, $list ){
				var $l = $list.clone();
				this.getRangeElements( range, blockElements ).each(function(i){
					var $this = $(this);
					if ( $this.parent().is('ul') )
					{
						replaceElement( $this.parent(), 'ol' );
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
					replaceElement( $(this), 'li' );
				});
			}, $list );
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}
	function insertOrderedList()
	{
		toggleOrderedList();
	}
	function isOrderedList()
	{
		return ( selectionIsWithin('ol') || document.queryCommandState('insertOrderedList') );
	}
	function toggleUnorderedList()
	{
		var
		$list = $('<ul/>');

		if ( isUnorderedList() )
		{
			this.manipulateSelection(function( range, $list ){
				this.getRangeElements( range, 'ul' ).each(function(i){
					var $this = $(this);
					$this.children('li').each(function(){
						var $this = $(this);
						replaceElement( $this, 'p' );
						$this.find('ol,ul').each(function(){
							var	$parent = $(this).parent();
							if ( $parent.is('p') )
							{
								deleteElement.apply( $parent );
							}
						});
					});
					deleteElement.apply( $this );
				});
			});
		}
		else
		{
			this.manipulateSelection(function( range, $list ){
				var $l = $list.clone();
				this.getRangeElements( range, blockElements ).each(function(i){
					var $this = $(this);
					if ( $this.parent().is('ol') )
					{
						replaceElement( $this.parent(), 'ul' );
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
					replaceElement( $(this), 'li' );
				});
			}, $list );
		}
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}
	function insertUnorderedList()
	{
		toggleUnorderedList();
	}
	function isUnorderedList()
	{
		return ( selectionIsWithin('ul') || document.queryCommandState('insertUnorderedList') );
	}


	function insertImage( url, attrs )
	{
		this.execCommand('insertImage', false, url);
	}


	function insertHTML(html)
	{
		if ( $.browser.msie )
		{
			var range = DOC.selection.createRange();
			range.pasteHTML(html);
			range.collapse(false);
			range.select();
			$(DOC.activeElement).trigger( CHANGE_EVT );
		}
		else
		{
			this.execCommand('insertHTML', false, html);
		}
	}

	function wrapHTML()
	{
		var
		selection	= WIN.getSelection(),
		range		= selection.getRangeAt(0),
		node		= selection.getNode(),
		argLength	= arguments.length,
		el;

		if (range.collapsed)
		{
			range = DOC.createRange();
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
		$(DOC.activeElement).trigger( CHANGE_EVT );
	}

	function changeContentBlock( tagName )
	{
		var
		selection	= WIN.getSelection(),
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

			this.getRangeElements( range, blockElements )
				.each(function(){
					editor.replaceElement( $(this), tagName );
				 })
				.data( replaced, true );

		}
		$editor
			.children( tagName )
			.removeData( replaced );

		$(DOC.activeElement).trigger( CHANGE_EVT );

		this.restoreRanges( ranges );
	}

	function unformatContentBlock()
	{
		this.changeContentBlock('p');
	}

	function replaceElement( $el, tagName )
	{
		if ( $el.hasClass( WYSIHAT_EDITOR ) )
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
	}

	function deleteElement()
	{
		var $this = $(this);
		$this.replaceWith( $this.html() );

		$(DOC.activeElement).trigger( CHANGE_EVT );
	}

	function stripFormattingElements()
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
		selection	= WIN.getSelection(),
		isFormatter	= WysiHat.Element.isFormatter,
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range = selection.getRangeAt( i );
			ranges.push( range );
			this.getRangeElements( range, blockElements ).each( stripFormatters );
		}

		$(DOC.activeElement).trigger( CHANGE_EVT );

		this.restoreRanges( ranges );
	}

	function isValidCommand( cmd )
	{
		return ( $.inArray( cmd, validCommands ) > -1 );
	}
	function execCommand( command, ui, value )
	{
		noSpans();
		try
		{
			DOC.execCommand(command, ui, value);
		}
		catch(e)
		{
			return null;
		}

		$(DOC.activeElement).trigger( CHANGE_EVT );
	}
	function noSpans()
	{	
		try {
			DOC.execCommand('styleWithCSS', 0, false);
			noSpans = function(){
				DOC.execCommand('styleWithCSS', 0, false);
			};
		} catch (e) {
			try {
				DOC.execCommand('useCSS', 0, true);
				noSpans = function(){
					DOC.execCommand('useCSS', 0, true);
				};
			} catch (e) {
				try {
					DOC.execCommand('styleWithCSS', false, false);
					noSpans = function(){
						DOC.execCommand('styleWithCSS', false, false);
					};
				}
				catch (e) {}
			}
		}
	}
	noSpans();

	function queryCommandState(state)
	{
		var handler = this.queryCommands[state];
		if ( handler )
		{
			return handler();
		}
		
		try {
			return DOC.queryCommandState(state);
		}
		catch(e) { return null; }
	}

	function getSelectedStyles()
	{
		var
		selection = window.getSelection(),
		$node = $(selection.getNode()),
		styles = {};

		for (var s in this.styleSelectors) {
			styles[s] = $node.css(this.styleSelectors[s]);
		}
		return styles;
	}

	function toggleHTML( e )
	{
		var
		HTML	= false,
		$editor	= $(this),
		$target	= $( e.target ),
		text	= $target.text(),
		$btn	= $target.closest( 'button,[role=button]' ),
		$field	= $editor.data('field'),
		$tools	= $btn.siblings();

		if ( $btn.data('toggle-text') == undefined )
		{
			$btn.data('toggle-text','View Content');
		}

		this.toggleHTML = function()
		{
			if ( ! HTML )
			{
				$btn.find('b').text($btn.data('toggle-text'));
				$tools.hide();
				$editor.trigger('WysiHat-editor:change:immediate').hide();
				$field.show();
			}
			else
			{
				$btn.find('b').text(text);
				$tools.show();
				$field.trigger('WysiHat-field:change:immediate').hide();
				$editor.show();
			}
			HTML = ! HTML;
		};

		this.toggleHTML();
	}


	function manipulateSelection()
	{
		var
		selection	= WIN.getSelection(),
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
		$(DOC.activeElement).trigger( CHANGE_EVT );
		this.restoreRanges( ranges );
	}
	function getRangeElements( range, tagNames )
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
	}
	function getRanges()
	{
		var
		selection	= WIN.getSelection(),
		i			= selection.rangeCount,
		ranges		= [],
		range;

		while ( i-- )
		{
			range	= selection.getRangeAt( i );
			ranges.push( range );
		}

		return ranges;
	}
	function restoreRanges( ranges )
	{
		var
		selection = WIN.getSelection(),
		i = ranges.length;

		selection.removeAllRanges();
		while ( i-- )
		{
			selection.addRange( ranges[i] );
		}
	}
	function selectionIsWithin( tagNames )
	{
		var
		phrases	= WysiHat.Element.getPhraseElements(),
		phrase	= false,
		tags	= tagNames.split(','),
		t		= tags.length,
		sel		= WIN.getSelection(),
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
	}

	function getBlockElements() {
		return blockElements;
	}


	return {
		boldSelection:				boldSelection,
		isBold:						isBold,
		italicizeSelection:			italicizeSelection,
		isItalic:					isItalic,
		underlineSelection:			underlineSelection,
		isUnderlined:				isUnderlined,
		strikethroughSelection:		strikethroughSelection,
		isStruckthrough:			isStruckthrough,

		quoteSelection:				quoteSelection,
		unquoteSelection:			unquoteSelection,
		toggleIndentation:			toggleIndentation,
		isIndented:					isIndented,

		fontSelection:				fontSelection,
		fontSizeSelection:			fontSizeSelection,
		colorSelection:				colorSelection,
		backgroundColorSelection:	backgroundColorSelection,

		alignSelection:				alignSelection,
		alignSelected:				alignSelected,

		linkSelection:				linkSelection,
		unlinkSelection:			unlinkSelection,
		isLinked:					isLinked,

		toggleOrderedList:			toggleOrderedList,
		insertOrderedList:			insertOrderedList,
		isOrderedList:				isOrderedList,
		toggleUnorderedList:		toggleUnorderedList,
		insertUnorderedList:		insertUnorderedList,
		isUnorderedList:			isUnorderedList,

		insertImage:				insertImage,

		insertHTML:					insertHTML,
		wrapHTML:					wrapHTML,

		changeContentBlock:			changeContentBlock,
		unformatContentBlock:		unformatContentBlock,
		replaceElement:				replaceElement,
		deleteElement:				deleteElement,
		stripFormattingElements:	stripFormattingElements,

		execCommand:				execCommand,
		noSpans:					noSpans,
		queryCommandState:			queryCommandState,
		getSelectedStyles:			getSelectedStyles,

		toggleHTML:					toggleHTML,

		isValidCommand:				isValidCommand,
		manipulateSelection:		manipulateSelection,
		getRangeElements:			getRangeElements,
		getRanges:					getRanges,
		restoreRanges:				restoreRanges,
		selectionIsWithin:			selectionIsWithin,

		getBlockElements:			getBlockElements,

		queryCommands: {
			bold:			isBold,
			italic:			isItalic,
			underline:		isUnderlined,
			strikethrough:	isStruckthrough,
			createLink:		isLinked,
			orderedlist:	isOrderedList,
			unorderedlist:	isUnorderedList
		},

		styleSelectors: {
			fontname:		'fontFamily',
			fontsize:		'fontSize',
			forecolor:		'color',
			hilitecolor:	'backgroundColor',
			backcolor:		'backgroundColor'
		}
	};
})( window, document, jQuery );


// ---------------------------------------------------------------------

/**
 * Paste Handler
 *
 * @todo normalize in compat and move logic to event system
 *
 * Normalizes the paste event for various browsers and controls
 * cursor reinsertion.
 */

// ---------------------------------------------------------------------

(function($){

	if ( ! $.browser.msie )
	{
		$('body')
			.delegate('.WysiHat-editor', 'contextmenu click doubleclick keydown', function(){

				var
				$editor		= $(this),
				$field		= $editor.data('field'),
				selection	= window.getSelection(),
				range		= selection.getRangeAt(0);

				if ( range )
				{
					range = range.cloneRange();
				}
				else
				{
					range = document.createRange();
					range.selectNode( $editor.get(0).firstChild );
				}

				$field.data(
					'saved-range',
					{
						startContainer:	range.startContainer,
						startOffset:	range.startOffset,
						endContainer: 	range.endContainer,
						endOffset:		range.endOffset
					}
				);
			 })
			.delegate('.WysiHat-editor', 'paste', function(e){
				var
				originalEvent	= e.originalEvent,
				$editor			= $(this),
				$field			= $editor.data('field');

				$field.data( 'original-html', $editor.children().detach() );

				if ( originalEvent.clipboardData &&
					 originalEvent.clipboardData.getData )
				{
					if ( /text\/html/.test( originalEvent.clipboardData.types ) )
					{
						$editor.html( originalEvent.clipboardData.getData('text/html') );
					}
					else if ( /text\/plain/.test( originalEvent.clipboardData.types ) )
					{
						$editor.html( originalEvent.clipboardData.getData('text/plain') );
					}
					else
					{
						$editor.html('');
					}
					waitforpastedata( $editor );
					originalEvent.stopPropagation();
					originalEvent.preventDefault();
					return false;
				}

				$editor.html('');
				waitforpastedata( $editor );
				return true;
			 });

			function waitforpastedata( $editor )
			{
				if ( $editor.contents().length )
				{
					processpaste( $editor );
				}
				else
				{
					setTimeout(function(){
						waitforpastedata( $editor );
					}, 20 );
				}
			}

			function processpaste( $editor )
			{
				$editor
					.remove('script,noscript,style,:hidden')
					.html( $editor.get(0).innerHTML.replace( /></g, '> <') );

				var
				$field			= $editor.data('field'),
				$originalHtml	= $field.data('original-html'),
				savedRange		= $field.data('saved-range'),
				range			= document.createRange(),

				pastedContent	= document.createDocumentFragment(),
				// Separates the pasted text into sections defined by two linebreaks
				// for conversion to paragraphs
				pastedText		= $editor.getPreText().split( /\n([ \t]*\n)+/g ),
				len				= pastedText.length,
				p				= document.createElement('p'),
				br				= document.createElement('br'),
				pClone			= null,
				empty			= /[\s\r\n]/g,
				comments		= /<!--[^>]*-->/g;

				// Loop through paragraphs as defined by our above regex
				$.each(pastedText, function(index, paragraph)
				{
					// Remove HTML comments, Word may insert these
					paragraph = paragraph.replace(comments, '');
					
					// If the paragraph is empty, skip it
					if (paragraph.replace(empty, '') == '')
					{
						return true;
					}
					
					// Split paragraph into single linebreaks to add <br> tags
					// to the end of the lines
					paragraph = paragraph.split(/[\r\n]/g);
					
					// We'll append each line of the paragraph to this node
					pFragment = document.createDocumentFragment();
					
					$.each(paragraph, function(index, para)
					{
						// Add the current text line to the fragment
						pFragment.appendChild(document.createTextNode(para));
						
						// If this isn't the end of the paragraph, add a <br> element
						// to the end
						if (index != paragraph.length - 1)
						{
							pFragment.appendChild(br.cloneNode(false));
						}
					});
					
					// If we are starting the paste outside an existing block element,
					// OR have moved on to other paragraphs in the array, wrap pasted
					// text in paragraph tags
					if (savedRange.startContainer == 'p' || index != 0)
					{
						pClone = p.cloneNode(false);
						pClone.appendChild(pFragment);
						
						pastedContent.appendChild(pClone);
					}
					// Otherwise, we are probably pasting text in the middle
					// of an existing block element, just pass the text along
					else
					{
						pastedContent.appendChild(pFragment);
					}
				});

				$editor
					.empty()
					.append($originalHtml);

				range.setStart(savedRange.startContainer, savedRange.startOffset);
				range.setEnd(savedRange.endContainer, savedRange.endOffset);
				
				if ( ! range.collapsed)
				{
					range.deleteContents();
				}
				

				// Grab the new node so we can select it
				var lastChild = pastedContent.childNodes[
					pastedContent.childNodes.length - 1
				];
				

				range.insertNode(pastedContent);
				
				WysiHat.Formatting.cleanup($editor);

				// Some browsers won't actually add the pasted
				// content to the selection, so we do that first
				range.selectNodeContents(lastChild);

				// The change event on $field triggers a full
				// editor content replacement. We grab the
				// location of the cursor before that happens

				var selectionUtil = $editor.data('selectionUtil'),
					before = selectionUtil.get(range);

				$editor.trigger('WysiHat-editor:change:immediate');
				$field.trigger('WysiHat-field:change:immediate');

				// And restore their cursor
				selectionUtil.set(before[1]);
			}

			// Getting text from contentEditable DIVs and retaining linebreaks
			// can be tricky cross-browser, so we'll use this to handle them all
			$.fn.getPreText = function()
			{
				var preText = $("<pre />").html(this.html());
				
				if ($.browser.webkit)
				{
					preText.find("div").replaceWith(function()
					{
						return "\n" + this.innerHTML;
					});
				}
				else if ($.browser.msie)
				{
					preText.find("p").replaceWith(function()
					{
						return this.innerHTML + "<br>";
					});
				}
				else if ($.browser.mozilla || $.browser.opera || $.browser.msie)
				{
					preText.find("br").replaceWith("\n");
				}
				
				return preText.text();
			};
		}
		else
		{
			$('body')
				.delegate('.WysiHat-editor', 'paste', function(){
					WysiHat.Formatting.cleanup( $(this) );

					$(this).trigger( 'WysiHat-editor:change:immediate' );
				 });
		}

})(jQuery);


// ---------------------------------------------------------------------

/**
 * Formatting Class
 *
 * Responsible for keeping the markup clean and compliant. Also
 * deals with keeping changes between the raw text and editor in
 * sync periodically.
 */

// ---------------------------------------------------------------------


(function($){

WysiHat.Formatting = {
	cleanup: function( $element )
	{
		var replaceElement = WysiHat.Commands.replaceElement;
		$element
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
			.find('p:empty')
				.remove();
	},
	format: function( $el )
	{
		var
		// @todo move this out of the function
		reBlocks = new RegExp( '(<(?:ul|ol)>|<\/(?:' + WysiHat.Element.getBlocks().join('|') + ')>)[\r\n]*', 'g' ),
		html = $el.html()
					.replace('<p>&nbsp;</p>','')
					.replace(/<br\/?><\/p>/,'</p>')
					.replace( reBlocks,'$1\n' )
					.replace(/\n+/,'\n')
					.replace(/<p>\n+<\/p>/,'');
		$el.html( html );
	},
	getBrowserMarkupFrom: function( $el )
	{
		var $container = $('<div>' + $el.val().replace(/\n/,'') + '</div>');

		this.cleanup( $container );

		if ( $container.html() == '' ||
		 	 $container.html() == '<br>' ||
		 	 $container.html() == '<br/>' )
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

		if ( $container.html() == '' ||
		 	 $container.html() == '<br>' ||
		 	 $container.html() == '<br/>' )
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

})(jQuery);


// ---------------------------------------------------------------------

/**
 * Toolbar Class
 *
 * Handles the creation of the toolbar and manages the individual
 * buttons states. You can add your own by using:
 * $toolbar.addButton({ options });
 */

// ---------------------------------------------------------------------


(function($){

	WysiHat.Toolbar = function($el)
	{
		this.$editor = $el;
		this.$toolbar = $('<div class="' + WysiHat.name + '-editor-toolbar" role="presentation"></div>')
						.insertBefore( $el );
	}

	WysiHat.Toolbar.prototype = {

		addButtonSet: function(options)
		{
			var that = this;

			$(options.buttons).each(function(index, button){
				that.addButton(button);
			});
		},

		addButton: function( options, handler )
		{
			var name, $button;

			if ( ! options['name'] )
			{
				options['name'] = options['label'].toLowerCase();
			}
			name = options['name'];

			$button = this.createButtonElement( this.$toolbar, options );

			if ( handler )
			{
				options['handler'] = handler;
			}

			handler = this.buttonHandler( name, options );
			this.observeButtonClick( $button, handler );

			handler = this.buttonStateHandler( name, options );
			this.observeStateChanges( $button, name, handler );

			return $button;
		},

		createButtonElement: function( $toolbar, options )
		{
			var $btn = $('<button aria-pressed="false" tabindex="-1"><b>' + options['label'] + '</b></button>')
				.addClass( 'button ' + options['name'] )
				.appendTo( $toolbar )
				.hover(
					function(){
						var $button = $(this).closest('button');
						$button.attr('title',$button.find('b').text());
					},
					function(){
						$(this).closest('button').removeAttr('title');
					}
				);

			if ( options['cssClass'] )
			{
				$btn.addClass( options['cssClass'] );
			}

			if ( options['title'] )
			{
				$btn.attr('title',options['title']);
			}

			$btn.data( 'text', options['label'] );
			if ( options['toggle-text'] )
			{
				$btn.data( 'toggle-text', options['toggle-text'] );
			}

			return $btn;
		},

		buttonHandler: function( name, options )
		{
			var handler = $.noop;

			if ( options['handler'] )
			{
				return options['handler'];
			}
			else if ( WysiHat.Commands.isValidCommand( name ) )
			{
				if (WysiHat.Commands[name+'Selection'])
				{
					return function( $editor )
					{
						return $editor[name+'Selection']();
					};
				}

				return function( $editor )
				{
					return $editor.execCommand(name);
				};
			}

			return handler;
		},

		observeButtonClick: function( $button, handler )
		{
			var that = this;

			$button.click(function(e){

				// Bring focus to the editor before the handler is called
				// so that selection data is available to tools
				if ( ! that.$editor.is(':focus'))
				{
					that.$editor.focus();
				}


				// @pk before
				// Save the selection and current text so that we can
				// work out how to undo the change.
				var full_editor_before = that.$editor.html(),
					selectionUtil = that.$editor.data('selectionUtil'),
					before = selectionUtil.get(),
					after;
				
				handler( that.$editor, e );
				that.$editor.trigger( 'WysiHat-selection:change' );
				that.$editor.focus();

				// @pk after
				// Add the changes as an undo.
				after = selectionUtil.get();
				that.$editor.eventCore.textChange(full_editor_before, that.$editor.html(), before, after);

				return false;
			});

		},

		buttonStateHandler: function( name, options )
		{
			var handler = $.noop;
			if ( options['query'] )
			{
				handler = options['query'];
			}
			else if ( WysiHat.Commands.isValidCommand( name ) )
			{
				handler = function( $editor )
				{
					return $editor.queryCommandState(name);
				};
			}
			return handler;
		},

		observeStateChanges: function( $button, name, handler )
		{
			var
			that = this,
			previousState;

			that.$editor.bind( 'WysiHat-selection:change', function(){
				var state = handler( that.$editor, $button );
				if (state != previousState)
				{
					previousState = state;
					that.updateButtonState( $button, name, state );
				}
			});
		},

		updateButtonState: function( $button, name, state )
		{
			var
			text	= $button.data('text'),
			toggle	= $button.data('toggle-text');

			if ( state )
			{
				$button
					.addClass('selected')
					.attr('aria-pressed','true')
					.find('b')
						.text( toggle ? toggle : text );
			}
			else
			{
				$button
					.removeClass('selected')
					.attr('aria-pressed','false')
					.find('b')
						.text( text );
			}
		}
	};

	WysiHat.Toolbar.constructor = WysiHat.Toolbar;

})(jQuery);


// ---------------------------------------------------------------------

/**
 * Defaults and jQuery Binding
 *
 * This code sets up reasonable editor defaults and then adds
 * a convenience setup function to jQuery.fn that you can use
 * as $('textarea').wysihat(options).
 */

// ---------------------------------------------------------------------

WysiHat.Toolbar.ButtonSets = {};

WysiHat.Toolbar.ButtonSets.Basic = [
	{ label: "Bold" },
	{ label: "Underline" },
	{ label: "Italic" }
];

WysiHat.Toolbar.ButtonSets.Standard = [
	{ label: "Bold", cssClass: 'toolbar_button' },
	{ label: "Italic", cssClass: 'toolbar_button' },
	{ label: "Strikethrough", cssClass: 'toolbar_button' },
	{ label: "Bullets",
	  cssClass: 'toolbar_button', handler: function(editor) {
		return editor.toggleUnorderedList();
	  }
	}
];

jQuery.fn.wysihat = function(options) {
	options = jQuery.extend({
		buttons: WysiHat.Toolbar.ButtonSets.Standard
	}, options);

	return this.each(function(){
		var
		editor	= WysiHat.Editor.attach( jQuery(this) ),
		toolbar	= new WysiHat.Toolbar(editor);

		toolbar.addButtonSet(options);
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



jQuery.extend(Range.prototype, {

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

		jQuery(parent).children().each(function(){
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

(function( DOC, $ ) {

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
			var range = DOC.createRange();
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
			range		= DOC.createRange();

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