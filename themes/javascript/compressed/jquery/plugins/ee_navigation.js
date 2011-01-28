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

jQuery(document).ready(function(){var c=jQuery;EE.navigation={};var f=c("#navigationTabs"),h=c("#navigationTabs>li.parent"),k,l,g=false;EE.navigation.delay_show_next=function(){window.clearTimeout(k);g=true;k=window.setTimeout(function(){var a=c(l);a.parent().find(".active, .hover").removeClass("active").removeClass("hover");a.addClass("active").addClass("hover");EE.navigation.truncate_menus(a.children("ul"));g=false},60)};EE.navigation.mouse_listen=function(){f.mouseleave(function(){f.find(".active").removeClass("active")});
h.mouseenter(function(){if(f.find(".active").length){f.find(".active").removeClass("active");c(this).addClass("active")}});h.find("a.first_level").click(function(){var a=c(this).parent();a.hasClass("active")?a.removeClass("active"):a.addClass("active");return false});h.find("ul li").hover(function(){l=this;g||EE.navigation.delay_show_next()},function(){c(this).removeClass("hover");g||EE.navigation.untruncate_menus(c(this).children("ul"))}).find(".parent>a").click(function(){return false})};EE.navigation.move_top_level=
function(a,b,d){b.parents(".active").removeClass("active");b=b.closest("#navigationTabs>li");d&&b[d]().length&&a.setFocus(b[d]().children("a"))};EE.navigation.keyboard_listen=function(){f.ee_focus("a.first_level",{removeTabs:"a",onEnter:function(a){a=c(a.target).parent();if(a.hasClass("parent")){a.addClass("active");this.setFocus(a.find("ul>li>a").eq(0))}},onRight:function(a){a=c(a.target);var b=a.parent();if(b.hasClass("parent")&&!a.hasClass("first_level")){b.addClass("active");this.setFocus(b.find("ul>li>a").eq(0))}else EE.navigation.move_top_level(this,
b,"next")},onLeft:function(a){a=c(a.target);var b=a.parent();if(a.hasClass("first_level")&&b.prev().length)this.setFocus(b.prev().children("a"));else{b=b.parent().closest(".parent");b.removeClass("active");b.children("a.first_level").length?EE.navigation.move_top_level(this,b,"prev"):this.setFocus(b.children("a").eq(0))}},onUp:function(a){a=c(a.target);var b=a.parent(),d=b.prevAll(":not(.nav_divider)");!a.hasClass("first_level")&&b.prev.length&&this.setFocus(d.eq(0).children("a"))},onDown:function(a){a=
c(a.target);var b=a.parent(),d=b.nextAll(":not(.nav_divider)");if(!a.hasClass("first_level")&&d.length)this.setFocus(d.eq(0).children("a"));else if(b.hasClass("parent")){b.addClass("active");this.setFocus(b.find("ul>li>a").eq(0))}},onEscape:function(a){a=c(a.target).parent();EE.navigation.move_top_level(this,a)},onBlur:function(){this.getElements().parent.find(".active").removeClass("active")}})};EE.navigation.truncate_menus=function(a){var b=c(window).height();c.each(a,function(){var d=c(this),e=
d.offset().top,i=d.height(),j=d.find("li:first").height();e=e+i-b;i=d.find("> li:has(> a[href*=tgpref]):first:visible");if(e>0){j=Math.ceil(e/j)+2;e=d.find("> li.nav_divider:first:visible").prev().index();d.find("> li:visible").slice(e-j,e).hide()}else i.hide()})};EE.navigation.untruncate_menus=function(a){c.each(a,function(){var b=c(this);b.is(":visible")?setTimeout(function(){EE.navigation.untruncate_menus(a)},15):b.find("> li:hidden").show()})};EE.navigation.mouse_listen();EE.navigation.keyboard_listen()});
