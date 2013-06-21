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

jQuery(document).ready(function(){var a=jQuery;EE.navigation={};var e=a("#navigationTabs"),d=a("#navigationTabs>li.parent"),l,m,c=!1;EE.navigation.delay_show_next=function(){window.clearTimeout(l);c=!0;l=window.setTimeout(function(){var b=a(m);b.parent().find(".active, .hover").removeClass("active").removeClass("hover");b.addClass("active").addClass("hover");b.closest("#navigationTabs > li").is(d.first())||EE.navigation.truncate_menus(b.children("ul"));c=!1},100)};EE.navigation.mouse_listen=function(){e.mouseleave(function(){e.find(".active").removeClass("active")});
d.mouseenter(function(){e.find(".active").length&&(e.find(".active").removeClass("active"),a(this).addClass("active"))});d.find("a.first_level").click(function(){var b=a(this).parent();b.hasClass("active")?b.removeClass("active"):b.addClass("active");return!1});d.find("ul li").hover(function(){m=this;c||EE.navigation.delay_show_next()},function(){a(this).removeClass("hover");c||EE.navigation.untruncate_menus(a(this).children("ul"))}).find(".parent>a").click(function(){return!1})};EE.navigation.move_top_level=
function(b,a,h){a.parents(".active").removeClass("active");a=a.closest("#navigationTabs>li");h&&a[h]().length&&b.setFocus(a[h]().children("a"))};EE.navigation.truncate_menus=function(b){var e=a(window).height();a.each(b,function(b,d){var g=a(this),f=g.offset().top,c=g.height(),k=g.find("li:first").height(),f=f+c-e,c=g.find("> li:has(> a[href*=tgpref]):first:visible");0<f?(k=Math.ceil(f/k)+2,f=g.find("> li.nav_divider:first:visible").prev().index(),g.find("> li:visible").slice(f-k,f).hide()):c.hide()})};
EE.navigation.untruncate_menus=function(b){a.each(b,function(c,e){var d=a(this);d.is(":visible")?setTimeout(function(){EE.navigation.untruncate_menus(b)},15):d.find("> li:hidden").show()})};EE.navigation.mouse_listen()});
