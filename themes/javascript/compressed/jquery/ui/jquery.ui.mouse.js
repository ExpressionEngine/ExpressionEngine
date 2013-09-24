/*!
 * jQuery UI Mouse @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Mouse
 *
 * Depends:
 *	jquery.ui.widget.js
 */

(function(b,g){var d=!1;b(document).mouseup(function(a){d=!1});b.widget("ui.mouse",{options:{cancel:":input,option",distance:1,delay:0},_mouseInit:function(){var a=this;this.element.bind("mousedown."+this.widgetName,function(b){return a._mouseDown(b)}).bind("click."+this.widgetName,function(c){if(!0===b.data(c.target,a.widgetName+".preventClickEvent"))return b.removeData(c.target,a.widgetName+".preventClickEvent"),c.stopImmediatePropagation(),!1});this.started=!1},_mouseDestroy:function(){this.element.unbind("."+
this.widgetName);b(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate)},_mouseDown:function(a){if(!d){this._mouseStarted&&this._mouseUp(a);this._mouseDownEvent=a;var c=this,e=1==a.which,f="string"==typeof this.options.cancel&&a.target.nodeName?b(a.target).closest(this.options.cancel).length:!1;if(!e||f||!this._mouseCapture(a))return!0;this.mouseDelayMet=!this.options.delay;this.mouseDelayMet||(this._mouseDelayTimer=setTimeout(function(){c.mouseDelayMet=
!0},this.options.delay));if(this._mouseDistanceMet(a)&&this._mouseDelayMet(a)&&(this._mouseStarted=!1!==this._mouseStart(a),!this._mouseStarted))return a.preventDefault(),!0;!0===b.data(a.target,this.widgetName+".preventClickEvent")&&b.removeData(a.target,this.widgetName+".preventClickEvent");this._mouseMoveDelegate=function(a){return c._mouseMove(a)};this._mouseUpDelegate=function(a){return c._mouseUp(a)};b(document).bind("mousemove."+this.widgetName,this._mouseMoveDelegate).bind("mouseup."+this.widgetName,
this._mouseUpDelegate);a.preventDefault();return d=!0}},_mouseMove:function(a){if(b.browser.msie&&!(9<=document.documentMode||a.button))return this._mouseUp(a);if(this._mouseStarted)return this._mouseDrag(a),a.preventDefault();this._mouseDistanceMet(a)&&this._mouseDelayMet(a)&&((this._mouseStarted=!1!==this._mouseStart(this._mouseDownEvent,a))?this._mouseDrag(a):this._mouseUp(a));return!this._mouseStarted},_mouseUp:function(a){b(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+
this.widgetName,this._mouseUpDelegate);this._mouseStarted&&(this._mouseStarted=!1,a.target==this._mouseDownEvent.target&&b.data(a.target,this.widgetName+".preventClickEvent",!0),this._mouseStop(a));return!1},_mouseDistanceMet:function(a){return Math.max(Math.abs(this._mouseDownEvent.pageX-a.pageX),Math.abs(this._mouseDownEvent.pageY-a.pageY))>=this.options.distance},_mouseDelayMet:function(a){return this.mouseDelayMet},_mouseStart:function(a){},_mouseDrag:function(a){},_mouseStop:function(a){},_mouseCapture:function(a){return!0}})})(jQuery);
