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
 * ExpressionEngine Focus Manager Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
jQuery.each({focus:"FocusIn",blur:"FocusOut"},function(a,c){var d=c.toLowerCase();function b(f,g){var e=this;jQuery.each(jQuery.data(this,"events")[d]||[],function(h,j){j.call(e,f,g)})}jQuery.event.special[d]={setup:function(f,e){jQuery.event.add(this,($.browser.msie?d:"DOM"+c),b)},teardown:function(e){return false}}});EE.tabQueue=(function(c){var a={},b=[];c(document).bind("focusin",function(e){var d=c(e.target).closest(b.join(",")),f;if(d.length&&(d=d.eq(0).data("focusmanager"))){f=c.data(d)}c.each(a,function(h,i){if(f!=h){var g=i.getOptions();g.onBlur.call(i,e)}})});return{append:function(d){var e=c(d).data("focusmanager"),f=c.data(e);if(a[f]){delete a.id}a[f]=e;b.push(d)},prepend:function(d){}}})(jQuery);(function(d){var c={},a={circular:false,focusClass:"focused",onBlur:function(){}};d.each(["Left","Right","Up","Down","Escape","Enter"],function(e,f){c[d.ui.keyCode[f.toUpperCase()]]="on"+f;a["on"+f]=function(){}});function b(h,f,e){var g=h.parent,i=this;if(e.removeTabs){g.find(e.removeTabs).attr("tabIndex",-1)}h.children.attr("tabIndex",-1).eq(0).attr("tabIndex",0);g.bind("keydown",function(k){if(k.keyCode>36&&k.keyCode<41){k.preventDefault()}if(c[k.keyCode]){var j=e[c[k.keyCode]].call(i,k);if(j===true){d(k.target).trigger("click")}return j}});d.extend(this,{getElements:function(){return h},getSelectors:function(){return f},getOptions:function(){return e},setFocus:function(j){d("."+e.focusClass).removeClass(e.focusClass);j.addClass(e.focusClass);j.focus()}})}d.fn.ee_focus=function(i,h){var f,g,e;if(f=this.eq(0).data("focusmanager")){return f}e={parent:d(this).selector,children:i};g=d.extend({},a);h=d.extend(g,h);this.each(function(j){var k={parent:d(this),children:d(this).find(e.children)};f=new b(k,e,h);k.parent.data("focusmanager",f)});EE.tabQueue.append(e.parent);return f}})(jQuery);