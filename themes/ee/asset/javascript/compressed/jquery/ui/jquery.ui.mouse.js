/*!
 * jQuery UI Mouse @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/mouse/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./widget"],e):
// Browser globals
e(jQuery)}(function(e){var t=!1;return e(document).mouseup(function(){t=!1}),e.widget("ui.mouse",{version:"@VERSION",options:{cancel:"input,textarea,button,select,option",distance:1,delay:0},_mouseInit:function(){var t=this;this.element.bind("mousedown."+this.widgetName,function(e){return t._mouseDown(e)}).bind("click."+this.widgetName,function(s){return!0===e.data(s.target,t.widgetName+".preventClickEvent")?(e.removeData(s.target,t.widgetName+".preventClickEvent"),s.stopImmediatePropagation(),!1):void 0}),this.started=!1},
// TODO: make sure destroying one instance of mouse doesn't mess with
// other instances of mouse
_mouseDestroy:function(){this.element.unbind("."+this.widgetName),this._mouseMoveDelegate&&this.document.unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate)},_mouseDown:function(s){
// don't let more than one widget handle mouseStart
if(!t){this._mouseMoved=!1,
// we may have missed mouseup (out of window)
this._mouseStarted&&this._mouseUp(s),this._mouseDownEvent=s;var i=this,o=1===s.which,
// event.target.nodeName works around a bug in IE 8 with
// disabled inputs (#7620)
u="string"==typeof this.options.cancel&&s.target.nodeName?e(s.target).closest(this.options.cancel).length:!1;
// Click event may never have fired (Gecko & Opera)
// these delegates are required to keep context
return o&&!u&&this._mouseCapture(s)?(this.mouseDelayMet=!this.options.delay,this.mouseDelayMet||(this._mouseDelayTimer=setTimeout(function(){i.mouseDelayMet=!0},this.options.delay)),this._mouseDistanceMet(s)&&this._mouseDelayMet(s)&&(this._mouseStarted=this._mouseStart(s)!==!1,!this._mouseStarted)?(s.preventDefault(),!0):(!0===e.data(s.target,this.widgetName+".preventClickEvent")&&e.removeData(s.target,this.widgetName+".preventClickEvent"),this._mouseMoveDelegate=function(e){return i._mouseMove(e)},this._mouseUpDelegate=function(e){return i._mouseUp(e)},this.document.bind("mousemove."+this.widgetName,this._mouseMoveDelegate).bind("mouseup."+this.widgetName,this._mouseUpDelegate),s.preventDefault(),t=!0,!0)):!0}},_mouseMove:function(t){
// Only check for mouseups outside the document if you've moved inside the document
// at least once. This prevents the firing of mouseup in the case of IE<9, which will
// fire a mousemove event if content is placed under the cursor. See #7778
// Support: IE <9
if(this._mouseMoved){
// IE mouseup check - mouseup happened when mouse was out of window
if(e.ui.ie&&(!document.documentMode||document.documentMode<9)&&!t.button)return this._mouseUp(t);if(!t.which)return this._mouseUp(t)}return(t.which||t.button)&&(this._mouseMoved=!0),this._mouseStarted?(this._mouseDrag(t),t.preventDefault()):(this._mouseDistanceMet(t)&&this._mouseDelayMet(t)&&(this._mouseStarted=this._mouseStart(this._mouseDownEvent,t)!==!1,this._mouseStarted?this._mouseDrag(t):this._mouseUp(t)),!this._mouseStarted)},_mouseUp:function(s){return this.document.unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate),this._mouseStarted&&(this._mouseStarted=!1,s.target===this._mouseDownEvent.target&&e.data(s.target,this.widgetName+".preventClickEvent",!0),this._mouseStop(s)),t=!1,!1},_mouseDistanceMet:function(e){return Math.max(Math.abs(this._mouseDownEvent.pageX-e.pageX),Math.abs(this._mouseDownEvent.pageY-e.pageY))>=this.options.distance},_mouseDelayMet:function(){return this.mouseDelayMet},
// These are placeholder methods, to be overriden by extending plugin
_mouseStart:function(){},_mouseDrag:function(){},_mouseStop:function(){},_mouseCapture:function(){return!0}})});