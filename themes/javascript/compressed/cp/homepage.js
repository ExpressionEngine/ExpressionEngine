/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

$(document).ready(function(){var a={},e=$('<div id="ajaxContent" />');a[EE.lang.close]=function(){$(this).dialog("close")};e.dialog({autoOpen:!1,resizable:!1,modal:!0,position:"center",minHeight:"0",buttons:a});$("a.submenu").click(function(){if($(this).data("working"))return!1;$(this).data("working",!0);var a=$(this).attr("href"),b=$(this).parent(),c=b.find("ul"),d;$(this).hasClass("accordion")?(0<c.length&&(b.hasClass("open")||b.siblings(".open").toggleClass("open").children("ul").slideUp("fast"),
c.slideToggle("fast"),b.toggleClass("open")),$(this).data("working",!1)):($(this).data("working",!1),d=$(this).html(),$("#ajaxContent").load(a+" .pageContents",function(){$("#ajaxContent").dialog("option","title",d);$("#ajaxContent").dialog("open")}));return!1})});
