/*
 * jQuery UI Selectable 1.8.1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Selectables
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.mouse.js
 *	jquery.ui.widget.js
 */

(function(e){e.widget("ui.selectable",e.ui.mouse,{options:{appendTo:"body",autoRefresh:!0,distance:0,filter:"*",tolerance:"touch"},_create:function(){var d=this;this.element.addClass("ui-selectable");this.dragged=!1;var f;this.refresh=function(){f=e(d.options.filter,d.element[0]);f.each(function(){var c=e(this),b=c.offset();e.data(this,"selectable-item",{element:this,$element:c,left:b.left,top:b.top,right:b.left+c.outerWidth(),bottom:b.top+c.outerHeight(),startselected:!1,selected:c.hasClass("ui-selected"),
selecting:c.hasClass("ui-selecting"),unselecting:c.hasClass("ui-unselecting")})})};this.refresh();this.selectees=f.addClass("ui-selectee");this._mouseInit();this.helper=e(document.createElement("div")).css({border:"1px dotted black"}).addClass("ui-selectable-helper")},destroy:function(){this.selectees.removeClass("ui-selectee").removeData("selectable-item");this.element.removeClass("ui-selectable ui-selectable-disabled").removeData("selectable").unbind(".selectable");this._mouseDestroy();return this},
_mouseStart:function(d){var f=this;this.opos=[d.pageX,d.pageY];if(!this.options.disabled){var c=this.options;this.selectees=e(c.filter,this.element[0]);this._trigger("start",d);e(c.appendTo).append(this.helper);this.helper.css({"z-index":100,position:"absolute",left:d.clientX,top:d.clientY,width:0,height:0});c.autoRefresh&&this.refresh();this.selectees.filter(".ui-selected").each(function(){var b=e.data(this,"selectable-item");b.startselected=!0;if(!d.metaKey)b.$element.removeClass("ui-selected"),
b.selected=!1,b.$element.addClass("ui-unselecting"),b.unselecting=!0,f._trigger("unselecting",d,{unselecting:b.element})});e(d.target).parents().andSelf().each(function(){var b=e.data(this,"selectable-item");if(b)return b.$element.removeClass("ui-unselecting").addClass("ui-selecting"),b.unselecting=!1,b.selecting=!0,b.selected=!0,f._trigger("selecting",d,{selecting:b.element}),!1})}},_mouseDrag:function(d){var f=this;this.dragged=!0;if(!this.options.disabled){var c=this.options,b=this.opos[0],g=this.opos[1],
i=d.pageX,j=d.pageY;if(b>i)var h=i,i=b,b=h;g>j&&(h=j,j=g,g=h);this.helper.css({left:b,top:g,width:i-b,height:j-g});this.selectees.each(function(){var a=e.data(this,"selectable-item");if(a&&a.element!=f.element[0]){var h=!1;c.tolerance=="touch"?h=!(a.left>i||a.right<b||a.top>j||a.bottom<g):c.tolerance=="fit"&&(h=a.left>b&&a.right<i&&a.top>g&&a.bottom<j);if(h){if(a.selected)a.$element.removeClass("ui-selected"),a.selected=!1;if(a.unselecting)a.$element.removeClass("ui-unselecting"),a.unselecting=!1;
if(!a.selecting)a.$element.addClass("ui-selecting"),a.selecting=!0,f._trigger("selecting",d,{selecting:a.element})}else{if(a.selecting)if(d.metaKey&&a.startselected)a.$element.removeClass("ui-selecting"),a.selecting=!1,a.$element.addClass("ui-selected"),a.selected=!0;else{a.$element.removeClass("ui-selecting");a.selecting=!1;if(a.startselected)a.$element.addClass("ui-unselecting"),a.unselecting=!0;f._trigger("unselecting",d,{unselecting:a.element})}if(a.selected&&!d.metaKey&&!a.startselected)a.$element.removeClass("ui-selected"),
a.selected=!1,a.$element.addClass("ui-unselecting"),a.unselecting=!0,f._trigger("unselecting",d,{unselecting:a.element})}}});return!1}},_mouseStop:function(d){var f=this;this.dragged=!1;e(".ui-unselecting",this.element[0]).each(function(){var c=e.data(this,"selectable-item");c.$element.removeClass("ui-unselecting");c.unselecting=!1;c.startselected=!1;f._trigger("unselected",d,{unselected:c.element})});e(".ui-selecting",this.element[0]).each(function(){var c=e.data(this,"selectable-item");c.$element.removeClass("ui-selecting").addClass("ui-selected");
c.selecting=!1;c.selected=!0;c.startselected=!0;f._trigger("selected",d,{selected:c.element})});this._trigger("stop",d);this.helper.remove();return!1}});e.extend(e.ui.selectable,{version:"1.8.1"})})(jQuery);
