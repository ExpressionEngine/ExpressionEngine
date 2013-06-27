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

jQuery(document).ready(function(){var b=jQuery;EE.navigation={};var e=b("#navigationTabs"),d=b("#navigationTabs>li.parent"),k,l,c=!1;EE.navigation.delay_show_next=function(){window.clearTimeout(k);c=!0;k=window.setTimeout(function(){var a=b(l);a.parent().find(".active, .hover").removeClass("active").removeClass("hover");a.addClass("active").addClass("hover");a.closest("#navigationTabs > li").is(d.first())||EE.navigation.truncate_menus(a.children("ul"));c=!1},100)};EE.navigation.mouse_listen=function(){e.mouseleave(function(){e.find(".active").removeClass("active")});
d.mouseenter(function(){e.find(".active").length&&(e.find(".active").removeClass("active"),b(this).addClass("active"))});d.find("a.first_level").click(function(){var a=b(this).parent();a.hasClass("active")?a.removeClass("active"):a.addClass("active");return!1});d.find("ul li").hover(function(){l=this;c||EE.navigation.delay_show_next()},function(){b(this).removeClass("hover");c||EE.navigation.untruncate_menus(b(this).children("ul"))}).find(".parent>a").click(function(){return!1})};EE.navigation.truncate_menus=
function(a){var e=b(window).height();b.each(a,function(a,d){var g=b(this),f=g.offset().top,c=g.height(),h=g.find("li:first").height(),f=f+c-e,c=g.find("> li:has(> a[href*=tgpref]):first:visible");0<f?(h=Math.ceil(f/h)+2,f=g.find("> li.nav_divider:first:visible").prev().index(),g.find("> li:visible").slice(f-h,f).hide()):c.hide()})};EE.navigation.untruncate_menus=function(a){b.each(a,function(c,e){var d=b(this);d.is(":visible")?setTimeout(function(){EE.navigation.untruncate_menus(a)},15):d.find("> li:hidden").show()})};
EE.navigation.mouse_listen()});
