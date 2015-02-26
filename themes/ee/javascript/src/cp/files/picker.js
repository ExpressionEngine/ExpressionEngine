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
/* This file exposes three callback functions:
 *
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow and
 * EE.manager.refreshPrefs
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('.modal-file').on('click', 'a:not([href=""])', function(e) {
			e.preventDefault();
			$(this).parents('div.box').load($(this).attr('href'));
		});
		$('.filepicker').click(function (e) {
			$("." + $(this).attr('rel') + " div.box").load($(this).attr('href'));
		});
		$('.modal-file').on('click', 'tr', function(e) {
			console.log($(this).find("input[type='checkbox']").val());
		});
	});
})(jQuery);
