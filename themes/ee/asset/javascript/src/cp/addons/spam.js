/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {
	$(".spam-detail").on('click', function(e) {
		var modal = "." + $(this).attr('rel');
		var heightIs = $(document).height();

		$('.overlay').fadeIn('slow').css('height',heightIs);
		$('.modal-wrap' + modal).fadeIn('slow');
		e.preventDefault();
		$('#top').animate({ scrollTop: 0 }, 100);

		modal = $(modal);
		modal.find('.date').html($(this).data('date'));
		modal.find('.ip').html($(this).data('ip'));
		modal.find('.content').html($(this).data('content'));
	});
})(jQuery);
