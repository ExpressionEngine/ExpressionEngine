/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function ($) {

"use strict";

EE.namespace('EE.design');


function localStorageSupported() {
	try {
		if ('localStorage' in window && window['localStorage'] !== null)
		{
			localStorage.setItem('ee_ping', 1);
			localStorage.removeItem('ee_ping');
			return true;
		}
	} catch (e) {
		return false;
	}
}

var store = localStorageSupported() ? localStorage : {

	setItem: function(k, v) {
		var time = new Date();
		time.setTime(time.getTime() + 5 * 1000); // expire in 5 seconds
		document.cookie = k + '=' + escape(v) + '; expires='+ time.toGMTString() +'; path=/';
	},

	removeItem: function(k) {
		document.cookie = k + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT';
	},

	getItem: function(k) {
		var regex = new RegExp('[,; ]' + k + '=([^\\s,;]*)'),
			cookies = ' ' + document.cookie,
			match = cookies.match(regex);

		return match ? unescape(match[1]) : undefined;
	}
};

// Hook up codemirror

function detectUseTabs(code)
{
	var tabs = code.match(/^\t+/gm),
		spaces = code.match(/^[ ]+/gm),
		tablength = tabs ? tabs.length : 0,
		spacelength = spaces ? spaces.length : 0;

	// default for new documents is tabs
	return (spacelength > tablength) ? false : true;
}

function createCodeMirror(code_textarea, mode = "ee")
{
	var orig_height = code_textarea.height();

	var code = code_textarea[0].value,
		usetabs = detectUseTabs(code);

	var cm = CodeMirror.fromTextArea(code_textarea[0], {
		lineWrapping: true,
		lineNumbers: true,
		autoCloseBrackets: true,
		styleActiveLine: true,
		showCursorWhenSelecting: true,
		mode: mode,
		smartIndent: false,
		indentWithTabs: usetabs,
		lint: EE.codemirror_linter
	});

	// Enable a code commenting command
	cm.setOption("extraKeys", {
		"Cmd-/": function(cm) {
		  cm.toggleComment({
			// Set the block comment tags to EE's syntax
			blockCommentStart: "{!--",
			blockCommentEnd: "--}",
		  })
		}
	  });

	  // Select a whole line when clicking on a gutter number
	  cm.on("gutterClick", function(cm, n) {
		cm.setSelection({ line: n, ch: 0}, {line: n + 1, ch:0})
		cm.focus()
	  })

	$('.CodeMirror').resizable({
		handles: "s",
		resize: function() {
			cm.setSize(null, $(this).height());
			cm.refresh();
		}
	});

	cm.setSize(null, orig_height);
	return cm;
}

$.fn.toggleCodeMirror = function (mode = "ee") {

	this.each(function() {

		var textarea = $(this),
			disabled = store.getItem('codemirror.disabled'),
			initialized = textarea.data('codemirror.initialized'),
			editor = textarea.data('codemirror.editor');

		if (typeof(initialized) === 'undefined' || ( ! initialized && ! disabled) || (initialized && disabled)) {
			editor = createCodeMirror(textarea, mode);
			store.removeItem('codemirror.disabled');
			textarea.data('codemirror.editor', editor);
		} else if (initialized) {
			editor.toTextArea();
			textarea.data('codemirror.editor', false);
			store.setItem('codemirror.disabled', true);
		}

		if (EE.editor.height) {
			editor.setSize(null, EE.editor.height);
		}

		// first call complete
		textarea.data('codemirror.initialized', true);
	});
};


})(jQuery);
