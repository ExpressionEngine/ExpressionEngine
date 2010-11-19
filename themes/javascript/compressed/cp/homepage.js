/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

$(document).ready(function(){var f={},i=$('<div id="ajaxContent" />'),a,b,d,e;f[EE.lang.close]=function(){$(this).dialog("close")};i.dialog({autoOpen:false,resizable:false,modal:true,position:"center",minHeight:"0",buttons:f});if(EE.importantMessage){a=EE.importantMessage.state;b=$("#ee_important_message");d=function(){a=!a;document.cookie="exp_home_msg_state="+(a?"open":"closed")};e=function(){$.ee_notice.show_info(function(){$.ee_notice.hide_info();b.removeClass("closed").show();d()})};b.find(".msg_open_close").click(function(){b.hide();
e();d()});a||e()}$("a.submenu").click(function(){if($(this).data("working"))return false;else $(this).data("working",true);var j=$(this).attr("href"),c=$(this).parent(),g=c.find("ul"),h;if($(this).hasClass("accordion")){if(g.length>0){c.hasClass("open")||c.siblings(".open").toggleClass("open").children("ul").slideUp("fast");g.slideToggle("fast");c.toggleClass("open")}$(this).data("working",false)}else{$(this).data("working",false);h=$(this).html();$("#ajaxContent").load(j+" .pageContents",function(){$("#ajaxContent").dialog("option",
"title",h);$("#ajaxContent").dialog("open")})}return false})});
