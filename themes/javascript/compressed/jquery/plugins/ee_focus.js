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

EE.tabQueue=function(b){var g={},f=[];b(document).focusin(function(e){var a=b(e.target).closest(f.join(",")),c;if(a.length&&(a=a.eq(0).data("focusmanager")))c=a[b.expando];b.each(g,function(b,a){c!=b&&a.getOptions().onBlur.call(a,e)})});return{append:function(e){var a=b(e).data("focusmanager"),c;b.data(a,"ee_focus_gen_id",!0);c=a[b.expando];g[c]&&delete g.id;g[c]=a;f.push(e)},prepend:function(){}}}(jQuery);
(function(b){function g(a,c,d){var e=a.parent,g=this;d.removeTabs&&e.find(d.removeTabs).attr("tabIndex",-1);a.children.attr("tabIndex",-1).eq(0).attr("tabIndex",0);e.bind("keydown",function(a){a.keyCode>36&&a.keyCode<41&&a.preventDefault();if(f[a.keyCode]){var c=d[f[a.keyCode]].call(g,a);c===!0&&b(a.target).trigger("click");return c}});b.extend(this,{getElements:function(){return a},getSelectors:function(){return c},getOptions:function(){return d},setFocus:function(a){b("."+d.focusClass).removeClass(d.focusClass);
a.addClass(d.focusClass);a.focus()}})}var f={},e={circular:!1,focusClass:"focused",onBlur:function(){}};b.each("Left,Right,Up,Down,Escape,Enter".split(","),function(a,c){f[b.ui.keyCode[c.toUpperCase()]]="on"+c;e["on"+c]=function(){}});b.fn.ee_focus=function(a,c){var d,f,h;if(d=this.eq(0).data("focusmanager"))return d;h={parent:b(this).selector,children:a};f=b.extend({},e);c=b.extend(f,c);this.each(function(){var a={parent:b(this),children:b(this).find(h.children)};d=new g(a,h,c);a.parent.data("focusmanager",
d)});EE.tabQueue.append(h.parent);return d}})(jQuery);
