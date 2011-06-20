/*!
 * jQuery UI Mouse 1.8.1
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Mouse
 *
 * Depends:
 *	jquery.ui.widget.js
 */

(function(c){c.widget("ui.mouse",{options:{cancel:":input,option",distance:1,delay:0},_mouseInit:function(){var a=this;this.element.bind("mousedown."+this.widgetName,function(b){return a._mouseDown(b)}).bind("click."+this.widgetName,function(b){if(a._preventClickEvent)return a._preventClickEvent=!1,b.stopImmediatePropagation(),!1});this.started=!1},_mouseDestroy:function(){this.element.unbind("."+this.widgetName)},_mouseDown:function(a){a.originalEvent=a.originalEvent||{};if(!a.originalEvent.mouseHandled){this._mouseStarted&&
this._mouseUp(a);this._mouseDownEvent=a;var b=this,d=a.which==1,e=typeof this.options.cancel=="string"?c(a.target).parents().add(a.target).filter(this.options.cancel).length:!1;if(!d||e||!this._mouseCapture(a))return!0;this.mouseDelayMet=!this.options.delay;if(!this.mouseDelayMet)this._mouseDelayTimer=setTimeout(function(){b.mouseDelayMet=!0},this.options.delay);if(this._mouseDistanceMet(a)&&this._mouseDelayMet(a)&&(this._mouseStarted=this._mouseStart(a)!==!1,!this._mouseStarted))return a.preventDefault(),
!0;this._mouseMoveDelegate=function(a){return b._mouseMove(a)};this._mouseUpDelegate=function(a){return b._mouseUp(a)};c(document).bind("mousemove."+this.widgetName,this._mouseMoveDelegate).bind("mouseup."+this.widgetName,this._mouseUpDelegate);c.browser.safari||a.preventDefault();return a.originalEvent.mouseHandled=!0}},_mouseMove:function(a){if(c.browser.msie&&!a.button)return this._mouseUp(a);if(this._mouseStarted)return this._mouseDrag(a),a.preventDefault();if(this._mouseDistanceMet(a)&&this._mouseDelayMet(a))(this._mouseStarted=
this._mouseStart(this._mouseDownEvent,a)!==!1)?this._mouseDrag(a):this._mouseUp(a);return!this._mouseStarted},_mouseUp:function(a){c(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate);if(this._mouseStarted)this._mouseStarted=!1,this._preventClickEvent=a.target==this._mouseDownEvent.target,this._mouseStop(a);return!1},_mouseDistanceMet:function(a){return Math.max(Math.abs(this._mouseDownEvent.pageX-a.pageX),Math.abs(this._mouseDownEvent.pageY-
a.pageY))>=this.options.distance},_mouseDelayMet:function(){return this.mouseDelayMet},_mouseStart:function(){},_mouseDrag:function(){},_mouseStop:function(){},_mouseCapture:function(){return!0}})})(jQuery);
