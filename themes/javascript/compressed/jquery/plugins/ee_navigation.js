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

jQuery(document).ready(function(){var b=jQuery;EE.navigation={};var f=b("#navigationTabs"),c=b("#navigationTabs>li.parent"),g,h,d=!1;EE.navigation.delay_show_next=function(){window.clearTimeout(g);d=!0;g=window.setTimeout(function(){var a=b(h);a.parent().find(".active, .hover").removeClass("active").removeClass("hover");a.addClass("active").addClass("hover");a.closest("#navigationTabs > li").is(c.first())||EE.navigation.truncate_menus(a.children("ul"));d=!1},100)};EE.navigation.mouse_listen=function(){f.mouseleave(function(){f.find(".active").removeClass("active")});
c.mouseenter(function(){f.find(".active").length&&(f.find(".active").removeClass("active"),b(this).addClass("active"))});c.find("a.first_level").click(function(){var a=b(this).parent();a.hasClass("active")?a.removeClass("active"):a.addClass("active");return!1});c.find("ul li").hover(function(){h=this;d||EE.navigation.delay_show_next()},function(){b(this).removeClass("hover");d||EE.navigation.untruncate_menus(b(this).children("ul"))}).find(".parent>a").click(function(){return!1})};EE.navigation.truncate_menus=
function(a){var f=b(window).height();b.each(a,function(){var a=b(this),e=a.offset().top,c=a.height(),d=a.find("li:first").height(),e=e+c-f,c=a.find("> li:has(> a[href*=tgpref]):first:visible");0<e?(d=Math.ceil(e/d)+2,e=a.find("> li.nav_divider:first:visible").prev().index(),a.find("> li:visible").slice(e-d,e).hide()):c.hide()})};EE.navigation.untruncate_menus=function(a){b.each(a,function(){var c=b(this);c.is(":visible")?setTimeout(function(){EE.navigation.untruncate_menus(a)},15):c.find("> li:hidden").show()})};
EE.navigation.mouse_listen()});
