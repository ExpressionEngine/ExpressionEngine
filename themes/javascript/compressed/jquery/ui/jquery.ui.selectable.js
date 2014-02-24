/*!
 * jQuery UI Selectable @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Selectables
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.mouse.js
 *	jquery.ui.widget.js
 */

(function(e,m){e.widget("ui.selectable",e.ui.mouse,{options:{appendTo:"body",autoRefresh:!0,distance:0,filter:"*",tolerance:"touch"},_create:function(){var b=this;this.element.addClass("ui-selectable");this.dragged=!1;var f;this.refresh=function(){f=e(b.options.filter,b.element[0]);f.addClass("ui-selectee");f.each(function(){var b=e(this),c=b.offset();e.data(this,"selectable-item",{element:this,$element:b,left:c.left,top:c.top,right:c.left+b.outerWidth(),bottom:c.top+b.outerHeight(),startselected:!1,
selected:b.hasClass("ui-selected"),selecting:b.hasClass("ui-selecting"),unselecting:b.hasClass("ui-unselecting")})})};this.refresh();this.selectees=f.addClass("ui-selectee");this._mouseInit();this.helper=e("<div class='ui-selectable-helper'></div>")},destroy:function(){this.selectees.removeClass("ui-selectee").removeData("selectable-item");this.element.removeClass("ui-selectable ui-selectable-disabled").removeData("selectable").unbind(".selectable");this._mouseDestroy();return this},_mouseStart:function(b){var f=
this;this.opos=[b.pageX,b.pageY];if(!this.options.disabled){var d=this.options;this.selectees=e(d.filter,this.element[0]);this._trigger("start",b);e(d.appendTo).append(this.helper);this.helper.css({left:b.clientX,top:b.clientY,width:0,height:0});d.autoRefresh&&this.refresh();this.selectees.filter(".ui-selected").each(function(){var c=e.data(this,"selectable-item");c.startselected=!0;b.metaKey||b.ctrlKey||(c.$element.removeClass("ui-selected"),c.selected=!1,c.$element.addClass("ui-unselecting"),c.unselecting=
!0,f._trigger("unselecting",b,{unselecting:c.element}))});e(b.target).parents().andSelf().each(function(){var c=e.data(this,"selectable-item");if(c){var d=!b.metaKey&&!b.ctrlKey||!c.$element.hasClass("ui-selected");c.$element.removeClass(d?"ui-unselecting":"ui-selected").addClass(d?"ui-selecting":"ui-unselecting");c.unselecting=!d;c.selecting=d;(c.selected=d)?f._trigger("selecting",b,{selecting:c.element}):f._trigger("unselecting",b,{unselecting:c.element});return!1}})}},_mouseDrag:function(b){var f=
this;this.dragged=!0;if(!this.options.disabled){var d=this.options,c=this.opos[0],g=this.opos[1],k=b.pageX,l=b.pageY;if(c>k)var h=k,k=c,c=h;g>l&&(h=l,l=g,g=h);this.helper.css({left:c,top:g,width:k-c,height:l-g});this.selectees.each(function(){var a=e.data(this,"selectable-item");if(a&&a.element!=f.element[0]){var h=!1;"touch"==d.tolerance?h=!(a.left>k||a.right<c||a.top>l||a.bottom<g):"fit"==d.tolerance&&(h=a.left>c&&a.right<k&&a.top>g&&a.bottom<l);h?(a.selected&&(a.$element.removeClass("ui-selected"),
a.selected=!1),a.unselecting&&(a.$element.removeClass("ui-unselecting"),a.unselecting=!1),a.selecting||(a.$element.addClass("ui-selecting"),a.selecting=!0,f._trigger("selecting",b,{selecting:a.element}))):(a.selecting&&((b.metaKey||b.ctrlKey)&&a.startselected?(a.$element.removeClass("ui-selecting"),a.selecting=!1,a.$element.addClass("ui-selected"),a.selected=!0):(a.$element.removeClass("ui-selecting"),a.selecting=!1,a.startselected&&(a.$element.addClass("ui-unselecting"),a.unselecting=!0),f._trigger("unselecting",
b,{unselecting:a.element}))),!a.selected||b.metaKey||b.ctrlKey||a.startselected||(a.$element.removeClass("ui-selected"),a.selected=!1,a.$element.addClass("ui-unselecting"),a.unselecting=!0,f._trigger("unselecting",b,{unselecting:a.element})))}});return!1}},_mouseStop:function(b){var f=this;this.dragged=!1;e(".ui-unselecting",this.element[0]).each(function(){var d=e.data(this,"selectable-item");d.$element.removeClass("ui-unselecting");d.unselecting=!1;d.startselected=!1;f._trigger("unselected",b,{unselected:d.element})});
e(".ui-selecting",this.element[0]).each(function(){var d=e.data(this,"selectable-item");d.$element.removeClass("ui-selecting").addClass("ui-selected");d.selecting=!1;d.selected=!0;d.startselected=!0;f._trigger("selected",b,{selected:d.element})});this._trigger("stop",b);this.helper.remove();return!1}});e.extend(e.ui.selectable,{version:"@VERSION"})})(jQuery);
