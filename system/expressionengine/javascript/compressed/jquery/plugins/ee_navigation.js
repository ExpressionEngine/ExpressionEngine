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

jQuery(document).ready(function(){function n(){if(!h){var a=d(h);a.parent().find("."+c+", ."+f).removeClass(c).removeClass(f);return a.addClass(c).addClass(f)}window.clearTimeout(m);i=true;m=window.setTimeout(function(){var b=d(h);b.parent().find("."+c+", ."+f).removeClass(c).removeClass(f);b.addClass(c).addClass(f);i=false},60)}function j(a,b,e){b.parents("."+c).removeClass(c);b=b.closest(k+">li");e&&b[e]().length&&a.setFocus(b[e]().children("a"))}var d=jQuery,c="active",f="hover",k="#navigationTabs",
g=d(k),l=d(k+">li.parent"),m,h,i=false;g.mouseleave(function(){g.find("."+c).removeClass(c)});l.mouseenter(function(){if(g.find("."+c).length){g.find("."+c).removeClass(c);d(this).addClass(c)}});l.find("a.first_level").click(function(){var a=d(this).parent();a.hasClass(c)?a.removeClass(c):a.addClass(c);return false});l.find("ul li").hover(function(){h=this;i||n()},function(){d(this).removeClass(f)}).find(".parent>a").click(function(){return false});g.ee_focus("a.first_level",{removeTabs:"a",onEnter:function(a){a=
d(a.target).parent();if(a.hasClass("parent")){a.addClass(c);this.setFocus(a.find("ul>li>a").eq(0))}},onRight:function(a){a=d(a.target);var b=a.parent();if(b.hasClass("parent")&&!a.hasClass("first_level")){b.addClass(c);this.setFocus(b.find("ul>li>a").eq(0))}else j(this,b,"next")},onLeft:function(a){a=d(a.target);var b=a.parent();if(a.hasClass("first_level")&&b.prev().length)this.setFocus(b.prev().children("a"));else{b=b.parent().closest(".parent");b.removeClass(c);b.children("a.first_level").length?
j(this,b,"prev"):this.setFocus(b.children("a").eq(0))}},onUp:function(a){a=d(a.target);var b=a.parent(),e=b.prevAll(":not(.nav_divider)");!a.hasClass("first_level")&&b.prev.length&&this.setFocus(e.eq(0).children("a"))},onDown:function(a){a=d(a.target);var b=a.parent(),e=b.nextAll(":not(.nav_divider)");if(!a.hasClass("first_level")&&e.length)this.setFocus(e.eq(0).children("a"));else if(b.hasClass("parent")){b.addClass(c);this.setFocus(b.find("ul>li>a").eq(0))}},onEscape:function(a){a=d(a.target).parent();
j(this,a)},onBlur:function(){this.getElements().parent.find("."+c).removeClass(c)}})});
