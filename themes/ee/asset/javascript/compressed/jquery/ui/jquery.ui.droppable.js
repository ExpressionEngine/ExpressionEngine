/*!
 * jQuery UI Droppable @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/droppable/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./widget","./mouse","./draggable"],e):
// Browser globals
e(jQuery)}(function(e){/*
	This manager tracks offsets of draggables and droppables
*/
return e.widget("ui.droppable",{version:"@VERSION",widgetEventPrefix:"drop",options:{accept:"*",activeClass:!1,addClasses:!0,greedy:!1,hoverClass:!1,scope:"default",tolerance:"intersect",
// callbacks
activate:null,deactivate:null,drop:null,out:null,over:null},_create:function(){var t,i=this.options,s=i.accept;this.isover=!1,this.isout=!0,this.accept=e.isFunction(s)?s:function(e){return e.is(s)},this.proportions=function(){
// Store the droppable's proportions
return arguments.length?void(t=arguments[0]):t?t:t={width:this.element[0].offsetWidth,height:this.element[0].offsetHeight}},this._addToManager(i.scope),i.addClasses&&this.element.addClass("ui-droppable")},_addToManager:function(t){
// Add the reference and positions to the manager
e.ui.ddmanager.droppables[t]=e.ui.ddmanager.droppables[t]||[],e.ui.ddmanager.droppables[t].push(this)},_splice:function(e){for(var t=0;t<e.length;t++)e[t]===this&&e.splice(t,1)},_destroy:function(){var t=e.ui.ddmanager.droppables[this.options.scope];this._splice(t),this.element.removeClass("ui-droppable ui-droppable-disabled")},_setOption:function(t,i){if("accept"===t)this.accept=e.isFunction(i)?i:function(e){return e.is(i)};else if("scope"===t){var s=e.ui.ddmanager.droppables[this.options.scope];this._splice(s),this._addToManager(i)}this._super(t,i)},_activate:function(t){var i=e.ui.ddmanager.current;this.options.activeClass&&this.element.addClass(this.options.activeClass),i&&this._trigger("activate",t,this.ui(i))},_deactivate:function(t){var i=e.ui.ddmanager.current;this.options.activeClass&&this.element.removeClass(this.options.activeClass),i&&this._trigger("deactivate",t,this.ui(i))},_over:function(t){var i=e.ui.ddmanager.current;
// Bail if draggable and droppable are same element
i&&(i.currentItem||i.element)[0]!==this.element[0]&&this.accept.call(this.element[0],i.currentItem||i.element)&&(this.options.hoverClass&&this.element.addClass(this.options.hoverClass),this._trigger("over",t,this.ui(i)))},_out:function(t){var i=e.ui.ddmanager.current;
// Bail if draggable and droppable are same element
i&&(i.currentItem||i.element)[0]!==this.element[0]&&this.accept.call(this.element[0],i.currentItem||i.element)&&(this.options.hoverClass&&this.element.removeClass(this.options.hoverClass),this._trigger("out",t,this.ui(i)))},_drop:function(t,i){var s=i||e.ui.ddmanager.current,o=!1;
// Bail if draggable and droppable are same element
// Bail if draggable and droppable are same element
return s&&(s.currentItem||s.element)[0]!==this.element[0]?(this.element.find(":data(ui-droppable)").not(".ui-draggable-dragging").each(function(){var i=e(this).droppable("instance");return i.options.greedy&&!i.options.disabled&&i.options.scope===s.options.scope&&i.accept.call(i.element[0],s.currentItem||s.element)&&e.ui.intersect(s,e.extend(i,{offset:i.element.offset()}),i.options.tolerance,t)?(o=!0,!1):void 0}),o?!1:this.accept.call(this.element[0],s.currentItem||s.element)?(this.options.activeClass&&this.element.removeClass(this.options.activeClass),this.options.hoverClass&&this.element.removeClass(this.options.hoverClass),this._trigger("drop",t,this.ui(s)),this.element):!1):!1},ui:function(e){return{draggable:e.currentItem||e.element,helper:e.helper,position:e.position,offset:e.positionAbs}}}),e.ui.intersect=function(){function e(e,t,i){return e>=t&&t+i>e}return function(t,i,s,o){if(!i.offset)return!1;var n=(t.positionAbs||t.position.absolute).left+t.margins.left,r=(t.positionAbs||t.position.absolute).top+t.margins.top,a=n+t.helperProportions.width,l=r+t.helperProportions.height,p=i.offset.left,h=i.offset.top,c=p+i.proportions().width,d=h+i.proportions().height;switch(s){case"fit":return n>=p&&c>=a&&r>=h&&d>=l;case"intersect":// Right Half
// Left Half
// Bottom Half
return p<n+t.helperProportions.width/2&&a-t.helperProportions.width/2<c&&h<r+t.helperProportions.height/2&&l-t.helperProportions.height/2<d;// Top Half
case"pointer":return e(o.pageY,h,i.proportions().height)&&e(o.pageX,p,i.proportions().width);case"touch":// Top edge touching
// Bottom edge touching
// Left edge touching
// Right edge touching
return(r>=h&&d>=r||l>=h&&d>=l||h>r&&l>d)&&(n>=p&&c>=n||a>=p&&c>=a||p>n&&a>c);default:return!1}}}(),e.ui.ddmanager={current:null,droppables:{"default":[]},prepareOffsets:function(t,i){var s,o,n=e.ui.ddmanager.droppables[t.options.scope]||[],r=i?i.type:null,// workaround for #2317
a=(t.currentItem||t.element).find(":data(ui-droppable)").addBack();e:for(s=0;s<n.length;s++)
// No disabled and non-accepted
if(!(n[s].options.disabled||t&&!n[s].accept.call(n[s].element[0],t.currentItem||t.element))){
// Filter out elements in the current dragged item
for(o=0;o<a.length;o++)if(a[o]===n[s].element[0]){n[s].proportions().height=0;continue e}n[s].visible="none"!==n[s].element.css("display"),n[s].visible&&(
// Activate the droppable if used directly from draggables
"mousedown"===r&&n[s]._activate.call(n[s],i),n[s].offset=n[s].element.offset(),n[s].proportions({width:n[s].element[0].offsetWidth,height:n[s].element[0].offsetHeight}))}},drop:function(t,i){var s=!1;
// Create a copy of the droppables in case the list changes during the drop (#9116)
return e.each((e.ui.ddmanager.droppables[t.options.scope]||[]).slice(),function(){this.options&&(!this.options.disabled&&this.visible&&e.ui.intersect(t,this,this.options.tolerance,i)&&(s=this._drop.call(this,i)||s),!this.options.disabled&&this.visible&&this.accept.call(this.element[0],t.currentItem||t.element)&&(this.isout=!0,this.isover=!1,this._deactivate.call(this,i)))}),s},dragStart:function(t,i){
// Listen for scrolling so that if the dragging causes scrolling the position of the droppables can be recalculated (see #5003)
t.element.parentsUntil("body").bind("scroll.droppable",function(){t.options.refreshPositions||e.ui.ddmanager.prepareOffsets(t,i)})},drag:function(t,i){
// If you have a highly dynamic page, you might try this option. It renders positions every time you move the mouse.
t.options.refreshPositions&&e.ui.ddmanager.prepareOffsets(t,i),
// Run through all droppables and check their positions based on specific tolerance options
e.each(e.ui.ddmanager.droppables[t.options.scope]||[],function(){if(!this.options.disabled&&!this.greedyChild&&this.visible){var s,o,n,r=e.ui.intersect(t,this,this.options.tolerance,i),a=!r&&this.isover?"isout":r&&!this.isover?"isover":null;a&&(this.options.greedy&&(
// find droppable parents with same scope
o=this.options.scope,n=this.element.parents(":data(ui-droppable)").filter(function(){return e(this).droppable("instance").options.scope===o}),n.length&&(s=e(n[0]).droppable("instance"),s.greedyChild="isover"===a)),
// we just moved into a greedy child
s&&"isover"===a&&(s.isover=!1,s.isout=!0,s._out.call(s,i)),this[a]=!0,this["isout"===a?"isover":"isout"]=!1,this["isover"===a?"_over":"_out"].call(this,i),
// we just moved out of a greedy child
s&&"isout"===a&&(s.isout=!1,s.isover=!0,s._over.call(s,i)))}})},dragStop:function(t,i){t.element.parentsUntil("body").unbind("scroll.droppable"),
// Call prepareOffsets one final time since IE does not fire return scroll events when overflow was caused by drag (see #5003)
t.options.refreshPositions||e.ui.ddmanager.prepareOffsets(t,i)}},e.ui.droppable});