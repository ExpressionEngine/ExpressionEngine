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

EE.tabQueue=function(a){var h={},i=[];a(document).focusin(function(f){var c=a(f.target).closest(i.join(",")),b;if(c.length&&(c=c.eq(0).data("focusmanager")))b=c[a.expando];a.each(h,function(d,g){b!=d&&g.getOptions().onBlur.call(g,f)})});return{append:function(f){var c=a(f).data("focusmanager"),b;a.data(c,"ee_focus_gen_id",true);b=c[a.expando];h[b]&&delete h.id;h[b]=c;i.push(f)},prepend:function(){}}}(jQuery);
(function(a){function h(c,b,d){var g=c.parent,j=this;d.removeTabs&&g.find(d.removeTabs).attr("tabIndex",-1);c.children.attr("tabIndex",-1).eq(0).attr("tabIndex",0);g.bind("keydown",function(e){e.keyCode>36&&e.keyCode<41&&e.preventDefault();if(i[e.keyCode]){var k=d[i[e.keyCode]].call(j,e);k===true&&a(e.target).trigger("click");return k}});a.extend(this,{getElements:function(){return c},getSelectors:function(){return b},getOptions:function(){return d},setFocus:function(e){a("."+d.focusClass).removeClass(d.focusClass);
e.addClass(d.focusClass);e.focus()}})}var i={},f={circular:false,focusClass:"focused",onBlur:function(){}};a.each(["Left","Right","Up","Down","Escape","Enter"],function(c,b){i[a.ui.keyCode[b.toUpperCase()]]="on"+b;f["on"+b]=function(){}});a.fn.ee_focus=function(c,b){var d,g,j;if(d=this.eq(0).data("focusmanager"))return d;j={parent:a(this).selector,children:c};g=a.extend({},f);b=a.extend(g,b);this.each(function(){var e={parent:a(this),children:a(this).find(j.children)};d=new h(e,j,b);e.parent.data("focusmanager",
d)});EE.tabQueue.append(j.parent);return d}})(jQuery);
