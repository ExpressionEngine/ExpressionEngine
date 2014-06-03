/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */


$(document).ready(function () {

	"use strict";

	// Hook up codemirror

	var code_textarea = $('#snippet_contents'),
		orig_height = code_textarea.height();

	var code = code_textarea[0].value,
		tabs = code.match(/^\t+/gm),
		spaces = code.match(/^[ ]+/gm),
		tablength = tabs ? tabs.length : 0,
		spacelength = spaces ? spaces.length : 0,
		usetabs = (spacelength > tablength) ? false : true; // this makes the default for new documents tabs

	var myCodeMirror = CodeMirror.fromTextArea(code_textarea[0], {
		lineNumbers: true,
		autoCloseBrackets: true,
		mode: "ee",
		smartIndent: false,
		indentWithTabs: usetabs
	});

	myCodeMirror.setSize(null, orig_height);
});