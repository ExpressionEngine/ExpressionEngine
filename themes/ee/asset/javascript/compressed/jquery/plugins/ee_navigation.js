/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
// ------------------------------------------------------------------------
/*!
 * ExpressionEngine Navigation Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
jQuery(document).ready(function(){var i=jQuery;EE.navigation={};
// Common strings to improve compressability
var n,e,t="active",a="hover",s="#navigationTabs",o="first_level",r="parent",l=i(s),u=i(s+">li."+r),v=!1;
// Mouse navigation
// -----------------------------------------------
EE.navigation.delay_show_next=function(){window.clearTimeout(n),v=!0,n=window.setTimeout(function(){var n=i(e);n.parent().find("."+t+", ."+a).removeClass(t).removeClass(a),n.addClass(t).addClass(a),n.closest("#navigationTabs > li").is(u.first())||EE.navigation.truncate_menus(n.children("ul")),v=!1},100)},EE.navigation.mouse_listen=function(){
// Mouse leaves nav - close all
l.mouseleave(function(){l.find("."+t).removeClass(t)}),
// Move along the main menu - each should open in turn
u.mouseenter(function(){l.find("."+t).length&&(l.find("."+t).removeClass(t),i(this).addClass(t))}),
// Toggle menu open / closed
u.find("a."+o).click(function(){var n=i(this).parent();return n.hasClass(t)?n.removeClass(t):n.addClass(t),!1}),
// Small delay in showing or hiding submenus to make mouse navigation smoother
u.find("ul li").hover(function(){e=this,v||EE.navigation.delay_show_next()},function(){i(this).removeClass(a),v||EE.navigation.untruncate_menus(i(this).children("ul"))}).find("."+r+">a").click(function(){return!1})},
// Menu Truncation
// -----------------------------------------------
/**
	 * Hide menu items when it would make the drop down menu too long;
	 * @param {jQuery Object} $menus jQuery collection of unordered lists representing submenus of the current hover
	 */
EE.navigation.truncate_menus=function(n){var e=i(window).height();i.each(n,function(n,t){var a=i(this),s=a.offset().top,o=a.height(),r=a.find("li:first").height(),l=s+o-e,u=a.find("> li:has(> a[href*=tgpref]):first:visible");if(l>0){var v=Math.ceil(l/r)+2,// Add more to lift it off the bottom
f=a.find("> li.nav_divider:first:visible").prev().index();a.find("> li:visible").slice(f-v,f).hide()}else u.hide()})},/**
	 * Reveal the hidden menu items so truncate_menus continues to work normally
	 * @param {jQuery Object} $menus jQuery collection of unordered lists representing submenus of the current hover
	 */
EE.navigation.untruncate_menus=function(n){i.each(n,function(e,t){var a=i(this);
// Check to see if the menu is visible, if it is, wait 15ms and try again
a.is(":visible")?setTimeout(function(){EE.navigation.untruncate_menus(n)},15):a.find("> li:hidden").show()})},EE.navigation.mouse_listen()});