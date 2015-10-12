/*!
 * jQuery UI Selectable @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/selectable/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./mouse","./widget"],e):
// Browser globals
e(jQuery)}(function(e){return e.widget("ui.selectable",e.ui.mouse,{version:"@VERSION",options:{appendTo:"body",autoRefresh:!0,distance:0,filter:"*",tolerance:"touch",
// callbacks
selected:null,selecting:null,start:null,stop:null,unselected:null,unselecting:null},_create:function(){var t,s=this;this.element.addClass("ui-selectable"),this.dragged=!1,
// cache selectee children based on filter
this.refresh=function(){t=e(s.options.filter,s.element[0]),t.addClass("ui-selectee"),t.each(function(){var t=e(this),s=t.offset();e.data(this,"selectable-item",{element:this,$element:t,left:s.left,top:s.top,right:s.left+t.outerWidth(),bottom:s.top+t.outerHeight(),startselected:!1,selected:t.hasClass("ui-selected"),selecting:t.hasClass("ui-selecting"),unselecting:t.hasClass("ui-unselecting")})})},this.refresh(),this.selectees=t.addClass("ui-selectee"),this._mouseInit(),this.helper=e("<div class='ui-selectable-helper'></div>")},_destroy:function(){this.selectees.removeClass("ui-selectee").removeData("selectable-item"),this.element.removeClass("ui-selectable ui-selectable-disabled"),this._mouseDestroy()},_mouseStart:function(t){var s=this,l=this.options;this.opos=[t.pageX,t.pageY],this.options.disabled||(this.selectees=e(l.filter,this.element[0]),this._trigger("start",t),e(l.appendTo).append(this.helper),
// position helper (lasso)
this.helper.css({left:t.pageX,top:t.pageY,width:0,height:0}),l.autoRefresh&&this.refresh(),this.selectees.filter(".ui-selected").each(function(){var l=e.data(this,"selectable-item");l.startselected=!0,t.metaKey||t.ctrlKey||(l.$element.removeClass("ui-selected"),l.selected=!1,l.$element.addClass("ui-unselecting"),l.unselecting=!0,
// selectable UNSELECTING callback
s._trigger("unselecting",t,{unselecting:l.element}))}),e(t.target).parents().addBack().each(function(){var l,i=e.data(this,"selectable-item");
// selectable (UN)SELECTING callback
return i?(l=!t.metaKey&&!t.ctrlKey||!i.$element.hasClass("ui-selected"),i.$element.removeClass(l?"ui-unselecting":"ui-selected").addClass(l?"ui-selecting":"ui-unselecting"),i.unselecting=!l,i.selecting=l,i.selected=l,l?s._trigger("selecting",t,{selecting:i.element}):s._trigger("unselecting",t,{unselecting:i.element}),!1):void 0}))},_mouseDrag:function(t){if(this.dragged=!0,!this.options.disabled){var s,l=this,i=this.options,n=this.opos[0],c=this.opos[1],a=t.pageX,r=t.pageY;return n>a&&(s=a,a=n,n=s),c>r&&(s=r,r=c,c=s),this.helper.css({left:n,top:c,width:a-n,height:r-c}),this.selectees.each(function(){var s=e.data(this,"selectable-item"),u=!1;
//prevent helper from being selected if appendTo: selectable
s&&s.element!==l.element[0]&&("touch"===i.tolerance?u=!(s.left>a||s.right<n||s.top>r||s.bottom<c):"fit"===i.tolerance&&(u=s.left>n&&s.right<a&&s.top>c&&s.bottom<r),u?(
// SELECT
s.selected&&(s.$element.removeClass("ui-selected"),s.selected=!1),s.unselecting&&(s.$element.removeClass("ui-unselecting"),s.unselecting=!1),s.selecting||(s.$element.addClass("ui-selecting"),s.selecting=!0,
// selectable SELECTING callback
l._trigger("selecting",t,{selecting:s.element}))):(
// UNSELECT
s.selecting&&((t.metaKey||t.ctrlKey)&&s.startselected?(s.$element.removeClass("ui-selecting"),s.selecting=!1,s.$element.addClass("ui-selected"),s.selected=!0):(s.$element.removeClass("ui-selecting"),s.selecting=!1,s.startselected&&(s.$element.addClass("ui-unselecting"),s.unselecting=!0),
// selectable UNSELECTING callback
l._trigger("unselecting",t,{unselecting:s.element}))),s.selected&&(t.metaKey||t.ctrlKey||s.startselected||(s.$element.removeClass("ui-selected"),s.selected=!1,s.$element.addClass("ui-unselecting"),s.unselecting=!0,
// selectable UNSELECTING callback
l._trigger("unselecting",t,{unselecting:s.element})))))}),!1}},_mouseStop:function(t){var s=this;return this.dragged=!1,e(".ui-unselecting",this.element[0]).each(function(){var l=e.data(this,"selectable-item");l.$element.removeClass("ui-unselecting"),l.unselecting=!1,l.startselected=!1,s._trigger("unselected",t,{unselected:l.element})}),e(".ui-selecting",this.element[0]).each(function(){var l=e.data(this,"selectable-item");l.$element.removeClass("ui-selecting").addClass("ui-selected"),l.selecting=!1,l.selected=!0,l.startselected=!0,s._trigger("selected",t,{selected:l.element})}),this._trigger("stop",t),this.helper.remove(),!1}})});