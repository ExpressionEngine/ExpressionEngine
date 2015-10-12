/*!
 * jQuery UI Spinner @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/spinner/
 */
!function(t){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./widget","./button"],t):
// Browser globals
t(jQuery)}(function(t){function i(t){return function(){var i=this.element.val();t.apply(this,arguments),this._refresh(),i!==this.element.val()&&this._trigger("change")}}return t.widget("ui.spinner",{version:"@VERSION",defaultElement:"<input>",widgetEventPrefix:"spin",options:{culture:null,icons:{down:"ui-icon-triangle-1-s",up:"ui-icon-triangle-1-n"},incremental:!0,max:null,min:null,numberFormat:null,page:10,step:1,change:null,spin:null,start:null,stop:null},_create:function(){
// handle string values that need to be parsed
this._setOption("max",this.options.max),this._setOption("min",this.options.min),this._setOption("step",this.options.step),
// Only format if there is a value, prevents the field from being marked
// as invalid in Firefox, see #9573.
""!==this.value()&&
// Format the value, but don't constrain.
this._value(this.element.val(),!0),this._draw(),this._on(this._events),this._refresh(),
// turning off autocomplete prevents the browser from remembering the
// value when navigating through history, so we re-enable autocomplete
// if the page is unloaded before the widget is destroyed. #7790
this._on(this.window,{beforeunload:function(){this.element.removeAttr("autocomplete")}})},_getCreateOptions:function(){var i={},e=this.element;return t.each(["min","max","step"],function(t,n){var s=e.attr(n);void 0!==s&&s.length&&(i[n]=s)}),i},_events:{keydown:function(t){this._start(t)&&this._keydown(t)&&t.preventDefault()},keyup:"_stop",focus:function(){this.previous=this.element.val()},blur:function(t){return this.cancelBlur?void delete this.cancelBlur:(this._stop(),this._refresh(),void(this.previous!==this.element.val()&&this._trigger("change",t)))},mousewheel:function(t,i){if(i){if(!this.spinning&&!this._start(t))return!1;this._spin((i>0?1:-1)*this.options.step,t),clearTimeout(this.mousewheelTimer),this.mousewheelTimer=this._delay(function(){this.spinning&&this._stop(t)},100),t.preventDefault()}},"mousedown .ui-spinner-button":function(i){function e(){var t=this.element[0]===this.document[0].activeElement;t||(this.element.focus(),this.previous=n,
// support: IE
// IE sets focus asynchronously, so we need to check if focus
// moved off of the input because the user clicked on the button.
this._delay(function(){this.previous=n}))}var n;
// We never want the buttons to have focus; whenever the user is
// interacting with the spinner, the focus should be on the input.
// If the input is focused then this.previous is properly set from
// when the input first received focus. If the input is not focused
// then we need to set this.previous based on the value before spinning.
n=this.element[0]===this.document[0].activeElement?this.previous:this.element.val(),
// ensure focus is on (or stays on) the text field
i.preventDefault(),e.call(this),
// support: IE
// IE doesn't prevent moving focus even with event.preventDefault()
// so we set a flag to know when we should ignore the blur event
// and check (again) if focus moved off of the input.
this.cancelBlur=!0,this._delay(function(){delete this.cancelBlur,e.call(this)}),this._start(i)!==!1&&this._repeat(null,t(i.currentTarget).hasClass("ui-spinner-up")?1:-1,i)},"mouseup .ui-spinner-button":"_stop","mouseenter .ui-spinner-button":function(i){
// button will add ui-state-active if mouse was down while mouseleave and kept down
// button will add ui-state-active if mouse was down while mouseleave and kept down
return t(i.currentTarget).hasClass("ui-state-active")?this._start(i)===!1?!1:void this._repeat(null,t(i.currentTarget).hasClass("ui-spinner-up")?1:-1,i):void 0},
// TODO: do we really want to consider this a stop?
// shouldn't we just stop the repeater and wait until mouseup before
// we trigger the stop event?
"mouseleave .ui-spinner-button":"_stop"},_draw:function(){var t=this.uiSpinner=this.element.addClass("ui-spinner-input").attr("autocomplete","off").wrap(this._uiSpinnerHtml()).parent().append(this._buttonHtml());this.element.attr("role","spinbutton"),
// button bindings
this.buttons=t.find(".ui-spinner-button").attr("tabIndex",-1).button().removeClass("ui-corner-all"),
// IE 6 doesn't understand height: 50% for the buttons
// unless the wrapper has an explicit height
this.buttons.height()>Math.ceil(.5*t.height())&&t.height()>0&&t.height(t.height()),
// disable spinner if element was already disabled
this.options.disabled&&this.disable()},_keydown:function(i){var e=this.options,n=t.ui.keyCode;switch(i.keyCode){case n.UP:return this._repeat(null,1,i),!0;case n.DOWN:return this._repeat(null,-1,i),!0;case n.PAGE_UP:return this._repeat(null,e.page,i),!0;case n.PAGE_DOWN:return this._repeat(null,-e.page,i),!0}return!1},_uiSpinnerHtml:function(){return"<span class='ui-spinner ui-widget ui-widget-content ui-corner-all'></span>"},_buttonHtml:function(){return"<a class='ui-spinner-button ui-spinner-up ui-corner-tr'><span class='ui-icon "+this.options.icons.up+"'>&#9650;</span></a><a class='ui-spinner-button ui-spinner-down ui-corner-br'><span class='ui-icon "+this.options.icons.down+"'>&#9660;</span></a>"},_start:function(t){return this.spinning||this._trigger("start",t)!==!1?(this.counter||(this.counter=1),this.spinning=!0,!0):!1},_repeat:function(t,i,e){t=t||500,clearTimeout(this.timer),this.timer=this._delay(function(){this._repeat(40,i,e)},t),this._spin(i*this.options.step,e)},_spin:function(t,i){var e=this.value()||0;this.counter||(this.counter=1),e=this._adjustValue(e+t*this._increment(this.counter)),this.spinning&&this._trigger("spin",i,{value:e})===!1||(this._value(e),this.counter++)},_increment:function(i){var e=this.options.incremental;return e?t.isFunction(e)?e(i):Math.floor(i*i*i/5e4-i*i/500+17*i/200+1):1},_precision:function(){var t=this._precisionOf(this.options.step);return null!==this.options.min&&(t=Math.max(t,this._precisionOf(this.options.min))),t},_precisionOf:function(t){var i=t.toString(),e=i.indexOf(".");return-1===e?0:i.length-e-1},_adjustValue:function(t){var i,e,n=this.options;
// clamp the value
// make sure we're at a valid step
// - find out where we are relative to the base (min or 0)
// - round to the nearest step
// - rounding is based on 0, so adjust back to our base
// fix precision from bad JS floating point math
// clamp the value
return i=null!==n.min?n.min:0,e=t-i,e=Math.round(e/n.step)*n.step,t=i+e,t=parseFloat(t.toFixed(this._precision())),null!==n.max&&t>n.max?n.max:null!==n.min&&t<n.min?n.min:t},_stop:function(t){this.spinning&&(clearTimeout(this.timer),clearTimeout(this.mousewheelTimer),this.counter=0,this.spinning=!1,this._trigger("stop",t))},_setOption:function(t,i){if("culture"===t||"numberFormat"===t){var e=this._parse(this.element.val());return this.options[t]=i,void this.element.val(this._format(e))}("max"===t||"min"===t||"step"===t)&&"string"==typeof i&&(i=this._parse(i)),"icons"===t&&(this.buttons.first().find(".ui-icon").removeClass(this.options.icons.up).addClass(i.up),this.buttons.last().find(".ui-icon").removeClass(this.options.icons.down).addClass(i.down)),this._super(t,i),"disabled"===t&&(this.widget().toggleClass("ui-state-disabled",!!i),this.element.prop("disabled",!!i),this.buttons.button(i?"disable":"enable"))},_setOptions:i(function(t){this._super(t)}),_parse:function(t){return"string"==typeof t&&""!==t&&(t=window.Globalize&&this.options.numberFormat?Globalize.parseFloat(t,10,this.options.culture):+t),""===t||isNaN(t)?null:t},_format:function(t){return""===t?"":window.Globalize&&this.options.numberFormat?Globalize.format(t,this.options.numberFormat,this.options.culture):t},_refresh:function(){this.element.attr({"aria-valuemin":this.options.min,"aria-valuemax":this.options.max,
// TODO: what should we do with values that can't be parsed?
"aria-valuenow":this._parse(this.element.val())})},isValid:function(){var t=this.value();
// null is invalid
// null is invalid
return null===t?!1:t===this._adjustValue(t)},
// update the value without triggering change
_value:function(t,i){var e;""!==t&&(e=this._parse(t),null!==e&&(i||(e=this._adjustValue(e)),t=this._format(e))),this.element.val(t),this._refresh()},_destroy:function(){this.element.removeClass("ui-spinner-input").prop("disabled",!1).removeAttr("autocomplete").removeAttr("role").removeAttr("aria-valuemin").removeAttr("aria-valuemax").removeAttr("aria-valuenow"),this.uiSpinner.replaceWith(this.element)},stepUp:i(function(t){this._stepUp(t)}),_stepUp:function(t){this._start()&&(this._spin((t||1)*this.options.step),this._stop())},stepDown:i(function(t){this._stepDown(t)}),_stepDown:function(t){this._start()&&(this._spin((t||1)*-this.options.step),this._stop())},pageUp:i(function(t){this._stepUp((t||1)*this.options.page)}),pageDown:i(function(t){this._stepDown((t||1)*this.options.page)}),value:function(t){return arguments.length?void i(this._value).call(this,t):this._parse(this.element.val())},widget:function(){return this.uiSpinner}})});