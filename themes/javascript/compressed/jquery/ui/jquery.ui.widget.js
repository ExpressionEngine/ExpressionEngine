/*!
 * jQuery UI Widget 1.8.1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Widget
 */

(function(c){var h=c.fn.remove;c.fn.remove=function(a,b){return this.each(function(){b||(!a||c.filter(a,[this]).length)&&c("*",this).add(this).each(function(){c(this).triggerHandler("remove")});return h.call(c(this),a,b)})};c.widget=function(a,b,e){var d=a.split(".")[0],f,a=a.split(".")[1];f=d+"-"+a;if(!e)e=b,b=c.Widget;c.expr[":"][f]=function(b){return!!c.data(b,a)};c[d]=c[d]||{};c[d][a]=function(a,c){arguments.length&&this._createWidget(a,c)};b=new b;b.options=c.extend({},b.options);c[d][a].prototype=
c.extend(!0,b,{namespace:d,widgetName:a,widgetEventPrefix:c[d][a].prototype.widgetEventPrefix||a,widgetBaseClass:f},e);c.widget.bridge(a,c[d][a])};c.widget.bridge=function(a,b){c.fn[a]=function(e){var d=typeof e==="string",f=Array.prototype.slice.call(arguments,1),g=this,e=!d&&f.length?c.extend.apply(null,[!0,e].concat(f)):e;if(d&&e.substring(0,1)==="_")return g;d?this.each(function(){var b=c.data(this,a),d=b&&c.isFunction(b[e])?b[e].apply(b,f):b;if(d!==b&&d!==void 0)return g=d,!1}):this.each(function(){var d=
c.data(this,a);d?(e&&d.option(e),d._init()):c.data(this,a,new b(e,this))});return g}};c.Widget=function(a,b){arguments.length&&this._createWidget(a,b)};c.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",options:{disabled:!1},_createWidget:function(a,b){this.element=c(b).data(this.widgetName,this);this.options=c.extend(!0,{},this.options,c.metadata&&c.metadata.get(b)[this.widgetName],a);var e=this;this.element.bind("remove."+this.widgetName,function(){e.destroy()});this._create();this._init()},
_create:function(){},_init:function(){},destroy:function(){this.element.unbind("."+this.widgetName).removeData(this.widgetName);this.widget().unbind("."+this.widgetName).removeAttr("aria-disabled").removeClass(this.widgetBaseClass+"-disabled ui-state-disabled")},widget:function(){return this.element},option:function(a,b){var e=a,d=this;if(arguments.length===0)return c.extend({},d.options);if(typeof a==="string"){if(b===void 0)return this.options[a];e={};e[a]=b}c.each(e,function(a,b){d._setOption(a,
b)});return d},_setOption:function(a,b){this.options[a]=b;a==="disabled"&&this.widget()[b?"addClass":"removeClass"](this.widgetBaseClass+"-disabled ui-state-disabled").attr("aria-disabled",b);return this},enable:function(){return this._setOption("disabled",!1)},disable:function(){return this._setOption("disabled",!0)},_trigger:function(a,b,e){var d=this.options[a],b=c.Event(b);b.type=(a===this.widgetEventPrefix?a:this.widgetEventPrefix+a).toLowerCase();e=e||{};if(b.originalEvent)for(var a=c.event.props.length,
f;a;)f=c.event.props[--a],b[f]=b.originalEvent[f];this.element.trigger(b,e);return!(c.isFunction(d)&&d.call(this.element[0],b,e)===!1||b.isDefaultPrevented())}}})(jQuery);
