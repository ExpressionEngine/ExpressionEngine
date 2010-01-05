/*
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
/*
 * ExpressionEngine Navigation Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
jQuery(document).ready(function(){var g=jQuery;var m="active",e="hover",i="#navigationTabs",j="first_level",k="parent",a=g(i),f=g(i+">li."+k),l,d,b=false;function h(){if(!d){var n=g(d);n.parent().find("."+m+", ."+e).removeClass(m).removeClass(e);return n.addClass(m).addClass(e)}window.clearTimeout(l);b=true;l=window.setTimeout(function(){var o=g(d);o.parent().find("."+m+", ."+e).removeClass(m).removeClass(e);o.addClass(m).addClass(e);b=false},60)}a.mouseleave(function(){a.find("."+m).removeClass(m)});f.mouseenter(function(){if(a.find("."+m).length){a.find("."+m).removeClass(m);g(this).addClass(m)}});f.find("a."+j).click(function(){var n=g(this).parent();if(n.hasClass(m)){n.removeClass(m)}else{n.addClass(m)}return false});f.find("ul li").hover(function(){d=this;if(!b){h()}},function(){g(this).removeClass(e)}).find("."+k+">a").click(function(){return false});function c(p,n,o){n.parents("."+m).removeClass(m);n=n.closest(i+">li");if(o&&n[o]().length){p.setFocus(n[o]().children("a"))}}a.ee_focus("a."+j,{removeTabs:"a",onEnter:function(o){var p=g(o.target),n=p.parent();if(n.hasClass(k)){n.addClass(m);this.setFocus(n.find("ul>li>a").eq(0))}},onRight:function(o){var p=g(o.target),n=p.parent();if(n.hasClass(k)&&!p.hasClass(j)){n.addClass(m);this.setFocus(n.find("ul>li>a").eq(0))}else{c(this,n,"next")}},onLeft:function(o){var p=g(o.target),n=p.parent();if(p.hasClass(j)&&n.prev().length){this.setFocus(n.prev().children("a"))}else{n=n.parent().closest("."+k);n.removeClass(m);if(n.children("a."+j).length){c(this,n,"prev")}else{this.setFocus(n.children("a").eq(0))}}},onUp:function(p){var q=g(p.target),n=q.parent(),o=n.prevAll(":not(.nav_divider)");if(!q.hasClass(j)&&n.prev.length){this.setFocus(o.eq(0).children("a"))}},onDown:function(p){var q=g(p.target),n=q.parent(),o=n.nextAll(":not(.nav_divider)");if(!q.hasClass(j)&&o.length){this.setFocus(o.eq(0).children("a"))}else{if(n.hasClass(k)){n.addClass(m);this.setFocus(n.find("ul>li>a").eq(0))}}},onEscape:function(o){var p=g(o.target),n=p.parent();c(this,n)},onBlur:function(n){this.getElements().parent.find("."+m).removeClass(m)}})});