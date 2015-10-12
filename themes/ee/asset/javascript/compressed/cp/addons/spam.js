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
"use strict";function updateVocabulary(t){var a=$(t).attr("href");$.ajax({url:a+"&method=updatevocab",success:function(a){"finished"!==a.status?($(t).html(a.message),updateVocabulary(t)):updateParameters(t)},dataType:"json"})}function updateParameters(t){var a=$(t).attr("href");$.ajax({url:a+"&method=updateparams",success:function(a){"finished"!==a.status?($(t).html(a.message),updateParameters(t)):($(t).html(a.finished),$(t).toggleClass("work"))},dataType:"json"})}!function(t){t(".spam-detail").on("click",function(a){var e="."+t(this).attr("rel"),s=t(document).height();t(".overlay").fadeIn("slow").css("height",s),t(".modal-wrap"+e).fadeIn("slow"),a.preventDefault(),t("#top").animate({scrollTop:0},100),e=t(e),e.find(".date").html(t(this).data("date")),e.find(".ip").html(t(this).data("ip")),e.find(".content").html(t(this).data("content"))}),t(".update").on("click",function(a){a.preventDefault();var e=this,s=t(this).attr("href");t(e).toggleClass("work"),t.ajax({url:s+"&method=download",success:function(a){"success"in a&&(t(e).html(a.success),t.ajax({url:s+"&method=prepare",success:function(t){"success"in t&&updateVocabulary(e)},dataType:"json"}))},dataType:"json"})})}(jQuery);