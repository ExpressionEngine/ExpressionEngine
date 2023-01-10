/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*
 @todo Documentation, reconsider class names

$(textarea).getSelectedText();
$(textarea).getSelectedRange();

selection = $(textarea).createSelection(start, end);
selection.replaceWith('[code]'+selection.getSelectedText()+'[/code]');

$(textarea).insertAtCursor('abc');
$(textarea).scrollToCursor();
*/

(function() {
	function Selection_Base(el) {
		this.el = el;

		this.lastIdx = -2;
		this.currentIdx = 0;

		if (document.selection) {
			this.range = this.el.createTextRange();
		}
	}

	Selection_Base.prototype = {

		createSelection: function(start, end) {
			this.el.focus();

			if ('selectionStart' in this.el) {
				this.el.selectionStart = start;
				this.el.selectionEnd = end;
			}
			else if (document.selection) {
				var r = document.selection.createRange();
				r.moveStart("character", -this.el.value.length);

				r.collapse();

				r.moveStart("character", start);
				r.moveEnd("character", end - start);
				r.select();
			}

			return this;
		},

		getSelectedText: function() {
			if ('selectionStart' in this.el) {
				return this.el.value.substr(this.el.selectionStart, this.el.selectionEnd - this.el.selectionStart);
			}
			else if (document.selection) {
				this.el.focus();
				return document.selection.createRange().text;
			}
		},

		getSelectedRange: function() {
			if ('selectionStart' in this.el) {
				return {start: this.el.selectionStart, end: this.el.selectionEnd};
			}
			else if (document.selection) {

				var r = document.selection.createRange(),
					selectionEnd = Math.abs(r.duplicate().moveEnd('character', -100000));
					selectionStart = selectionEnd - r.text.length;

				return {start: selectionStart, end: selectionEnd};
			}
		},

		replaceWith: function(text) {
			var newStart;
			var firstPart;
			var lastPart;

			this.el.focus();

			if ('selectionStart' in this.el) {

				if (localStorage.getItem('caretPosition')) {
					this.el.selectionStart = localStorage.getItem('caretPosition');
				}

				newStart = this.el.selectionStart + text.length;
				firstPart = this.el.value.substr(0, this.el.selectionStart);
				lastPart = this.el.value.substr(this.el.selectionStart);

				this.el.value = firstPart +
								text +
								lastPart;
				this.el.setSelectionRange(newStart, newStart);
			}
			else if (document.selection) {
				document.selection.createRange().text = text;
			}

			if (localStorage.getItem('caretPosition')) {
				localStorage.removeItem('caretPosition');
			}

			return this;
		},

		selectNext: function(find) {

			if ('selectionStart' in this.el) {

				var old = this.currentIdx, chunck;

				if (old > 0) {
					chunk = this.el.value.substring(this.currentIdx);
				}
				else {
					chunk = this.el.value;
				}

				this.currentIdx = chunk.indexOf(find);

				if (this.currentIdx != -1) {
					this.createSelection(old+this.currentIdx, old+this.currentIdx+find.length);
					this.lastIdx = old + this.currentIdx;
					this.currentIdx += old + find.length;
				}
				else if (this.lastIdx != this.currentIdx) {
					this.lastIdx = -1;
					this.currentIdx = 0;
					this.selectNext(find);
				}
			}
			else if (document.selection) {
				// This is actually easier in IE - whoa!

				this.el.focus();

				var res = this.range.findText(find, 1, 0);

				if (res) {
					this.range.select();
					this.range.collapse(false);
				}
				else {
					this.range = this.el.createTextRange();
				}
			}
		},

		resetCycle: function() {
			this.lastIdx = -2;
			this.currentIdx = 0;

			if (document.selection) {
				this.range = this.el.createTextRange();
			}
		}
	};

	if (jQuery) {

		function Selection(el) {
			Selection_Base.call(this, el);

			var textarea_line_height = 13,
				jQ_el = $(this.el),
				old_height = jQ_el.scrollTop(9999).scrollTop(),
				old_val = jQ_el.val();

			jQ_el.val(old_val + "\n");

			new_height = jQ_el.scrollTop(9999).scrollTop();
			jQ_el.val(old_val).scrollTop(0);

			this.textarea_line_height = new_height - old_height;
			this.jQ_el = jQ_el;
		}

		// Add any methods that require jQuery support

		var F = function() {};
		F.prototype = Selection_Base.prototype;
		Selection.prototype = new F();
		Selection.prototype.constructor = Selection;

		Selection.prototype.scrollToCursor = function() {

			// IE already does this when you create a selection, so we only hack
			// around the others

			if ('selectionStart' in this.el) {
				var boundaries = this.getSelectedRange(),
					lines = this.jQ_el.val().substr(0, boundaries.start).split("\n"),
					lineCount = lines.length;

				for (var i = 0; i < lines.length; i++) {
					length_ratio = lines[i].length / this.el.cols;
					if (length_ratio > 1) {
						lineCount += Math.ceil(length_ratio);
					}
				}

				lineCount = (lineCount > 5) ? lineCount - 5 : 0;
				this.jQ_el.scrollTop((lineCount - 5) * this.textarea_line_height);
			}

			return this;
		}
	}
	else {
		Selection = Selection_Base;
	}

	// ---------------------------------

	function Txtarea(el) {
		this.el = el;
		this.sel = new Selection(this.el);
	}

	Txtarea.prototype = {

		getSelectionObj: function() { return this.sel; },

		createSelection: function(start, end) { return this.sel.createSelection(start, end); },
		getSelectedText: function() { return this.sel.getSelectedText(); },
		getSelectedRange: function() { return this.sel.getSelectedRange(); },

		insertAtCursor: function(text) {
			this.sel.replaceWith(text);
		},
		selectNext: function(text) {
			this.sel.selectNext(text);
			return this.sel;
		},
		_resize: function() {

			var range = this.sel.getSelectedRange();

			if (range.start == range.end && range.end == this.el.value.length) {
				this.el.value += '\n';
				this.sel.createSelection(range.end, range.end);
			}

			if (this.el.scrollHeight > this.el.clientHeight) {
				$(this.el).height(this.el.scrollHeight + 10);
			}
		},
		autoResize: function() {
			var that = this,
				txt = $(this.el);

			txt.css('overflow', 'hidden');
			txt.keypress(function() {
				that._resize();
			});
			txt.keyup(function(event) {
				(event.keyCode == 13 && that._resize());
			});
		}
	};

	// ---------------------------------

	if (jQuery) {

		// And we want methods to be available the traditional jQuery way

		for (func in Txtarea.prototype) {
			jQuery.fn[func] = (function(f) {
				return function() {
					var args = Array.prototype.slice.call(arguments),
						jQ_Txt = this.data('txtarea');

					if ( ! jQ_Txt) {
						jQ_Txt = new Txtarea(this[0]);
						this.data('txtarea', jQ_Txt);
					}

					return jQ_Txt[f].apply(jQ_Txt, args);
				}
			})(func);
		}
	}

	window.Txtarea = Txtarea;
})();
