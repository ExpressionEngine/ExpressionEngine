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
$(document).ready(function(){"use strict";var t={},n=$('<div id="ajaxContent" />');t[EE.lang.close]=function(){$(this).dialog("close")},n.dialog({autoOpen:!1,resizable:!1,modal:!0,position:"center",minHeight:"0",buttons:t}),$("a.submenu").click(function(){if($(this).data("working"))return!1;$(this).data("working",!0);var t,n=$(this).attr("href"),a=$(this).parent(),i=a.find("ul");return $(this).hasClass("accordion")?(i.length>0&&(a.hasClass("open")||a.siblings(".open").toggleClass("open").children("ul").slideUp("fast"),i.slideToggle("fast"),a.toggleClass("open")),$(this).data("working",!1)):($(this).data("working",!1),t=$(this).html(),$("#ajaxContent").load(n+" .pageContents",function(){$("#ajaxContent").dialog("option","title",t),$("#ajaxContent").dialog("open")})),!1})});