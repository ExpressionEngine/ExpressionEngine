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
jQuery(document).ready(function(){var i=jQuery;EE.navigation={};var n,e,t="active",a="hover",s="#navigationTabs",o="first_level",r="parent",l=i(s),u=i(s+">li."+r),v=!1;EE.navigation.delay_show_next=function(){window.clearTimeout(n),v=!0,n=window.setTimeout(function(){var n=i(e);n.parent().find("."+t+", ."+a).removeClass(t).removeClass(a),n.addClass(t).addClass(a),n.closest("#navigationTabs > li").is(u.first())||EE.navigation.truncate_menus(n.children("ul")),v=!1},100)},EE.navigation.mouse_listen=function(){l.mouseleave(function(){l.find("."+t).removeClass(t)}),u.mouseenter(function(){l.find("."+t).length&&(l.find("."+t).removeClass(t),i(this).addClass(t))}),u.find("a."+o).click(function(){var n=i(this).parent();return n.hasClass(t)?n.removeClass(t):n.addClass(t),!1}),u.find("ul li").hover(function(){e=this,v||EE.navigation.delay_show_next()},function(){i(this).removeClass(a),v||EE.navigation.untruncate_menus(i(this).children("ul"))}).find("."+r+">a").click(function(){return!1})},EE.navigation.truncate_menus=function(n){var e=i(window).height();i.each(n,function(){var n=i(this),t=n.offset().top,a=n.height(),s=n.find("li:first").height(),o=t+a-e,r=n.find("> li:has(> a[href*=tgpref]):first:visible");if(o>0){var l=Math.ceil(o/s)+2,u=n.find("> li.nav_divider:first:visible").prev().index();n.find("> li:visible").slice(u-l,u).hide()}else r.hide()})},EE.navigation.untruncate_menus=function(n){i.each(n,function(){var e=i(this);e.is(":visible")?setTimeout(function(){EE.navigation.untruncate_menus(n)},15):e.find("> li:hidden").show()})},EE.navigation.mouse_listen()});