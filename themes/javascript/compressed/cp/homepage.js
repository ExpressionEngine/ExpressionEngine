/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

$(document).ready(function(){var e={},f=$('<div id="ajaxContent" />'),b,a,c,d;e[EE.lang.close]=function(){$(this).dialog("close")};f.dialog({autoOpen:!1,resizable:!1,modal:!0,position:"center",minHeight:"0",buttons:e});if(EE.importantMessage)b=EE.importantMessage.state,a=$("#ee_important_message"),c=function(){b=!b;document.cookie="exp_home_msg_state="+(b?"open":"closed")},d=function(){$.ee_notice.show_info(function(){$.ee_notice.hide_info();a.removeClass("closed").show();c()})},a.find(".msg_open_close").click(function(){a.hide();
d();c()}),b||d();$("a.submenu").click(function(){if($(this).data("working"))return!1;else $(this).data("working",!0);var b=$(this).attr("href"),a=$(this).parent(),c=a.find("ul"),d;$(this).hasClass("accordion")?(c.length>0&&(a.hasClass("open")||a.siblings(".open").toggleClass("open").children("ul").slideUp("fast"),c.slideToggle("fast"),a.toggleClass("open")),$(this).data("working",!1)):($(this).data("working",!1),d=$(this).html(),$("#ajaxContent").load(b+" .pageContents",function(){$("#ajaxContent").dialog("option",
"title",d);$("#ajaxContent").dialog("open")}));return!1})});
