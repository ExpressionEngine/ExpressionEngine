/*!
 * jQuery UI Progressbar @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/progressbar/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./widget"],e):
// Browser globals
e(jQuery)}(function(e){return e.widget("ui.progressbar",{version:"@VERSION",options:{max:100,value:0,change:null,complete:null},min:0,_create:function(){
// Constrain initial value
this.oldValue=this.options.value=this._constrainedValue(),this.element.addClass("ui-progressbar ui-widget ui-widget-content ui-corner-all").attr({
// Only set static values, aria-valuenow and aria-valuemax are
// set inside _refreshValue()
role:"progressbar","aria-valuemin":this.min}),this.valueDiv=e("<div class='ui-progressbar-value ui-widget-header ui-corner-left'></div>").appendTo(this.element),this._refreshValue()},_destroy:function(){this.element.removeClass("ui-progressbar ui-widget ui-widget-content ui-corner-all").removeAttr("role").removeAttr("aria-valuemin").removeAttr("aria-valuemax").removeAttr("aria-valuenow"),this.valueDiv.remove()},value:function(e){return void 0===e?this.options.value:(this.options.value=this._constrainedValue(e),void this._refreshValue())},_constrainedValue:function(e){
// sanitize value
return void 0===e&&(e=this.options.value),this.indeterminate=e===!1,"number"!=typeof e&&(e=0),this.indeterminate?!1:Math.min(this.options.max,Math.max(this.min,e))},_setOptions:function(e){
// Ensure "value" option is set after other values (like max)
var i=e.value;delete e.value,this._super(e),this.options.value=this._constrainedValue(i),this._refreshValue()},_setOption:function(e,i){"max"===e&&(
// Don't allow a max less than min
i=Math.max(this.min,i)),"disabled"===e&&this.element.toggleClass("ui-state-disabled",!!i).attr("aria-disabled",i),this._super(e,i)},_percentage:function(){return this.indeterminate?100:100*(this.options.value-this.min)/(this.options.max-this.min)},_refreshValue:function(){var i=this.options.value,t=this._percentage();this.valueDiv.toggle(this.indeterminate||i>this.min).toggleClass("ui-corner-right",i===this.options.max).width(t.toFixed(0)+"%"),this.element.toggleClass("ui-progressbar-indeterminate",this.indeterminate),this.indeterminate?(this.element.removeAttr("aria-valuenow"),this.overlayDiv||(this.overlayDiv=e("<div class='ui-progressbar-overlay'></div>").appendTo(this.valueDiv))):(this.element.attr({"aria-valuemax":this.options.max,"aria-valuenow":i}),this.overlayDiv&&(this.overlayDiv.remove(),this.overlayDiv=null)),this.oldValue!==i&&(this.oldValue=i,this._trigger("change")),i===this.options.max&&this._trigger("complete")}})});