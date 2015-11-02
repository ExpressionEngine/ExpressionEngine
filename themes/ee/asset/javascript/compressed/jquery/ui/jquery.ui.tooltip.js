/*!
 * jQuery UI Tooltip @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/tooltip/
 */
!function(t){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./core","./widget","./position"],t):
// Browser globals
t(jQuery)}(function(t){return t.widget("ui.tooltip",{version:"@VERSION",options:{content:function(){
// support: IE<9, Opera in jQuery <1.7
// .text() can't accept undefined, so coerce to a string
var i=t(this).attr("title")||"";
// Escape title, since we're going from an attribute to raw HTML
return t("<a>").text(i).html()},hide:!0,
// Disabled elements have inconsistent behavior across browsers (#8661)
items:"[title]:not([disabled])",position:{my:"left top+15",at:"left bottom",collision:"flipfit flip"},show:!0,tooltipClass:null,track:!1,
// callbacks
close:null,open:null},_addDescribedBy:function(i,e){var o=(i.attr("aria-describedby")||"").split(/\s+/);o.push(e),i.data("ui-tooltip-id",e).attr("aria-describedby",t.trim(o.join(" ")))},_removeDescribedBy:function(i){var e=i.data("ui-tooltip-id"),o=(i.attr("aria-describedby")||"").split(/\s+/),n=t.inArray(e,o);-1!==n&&o.splice(n,1),i.removeData("ui-tooltip-id"),o=t.trim(o.join(" ")),o?i.attr("aria-describedby",o):i.removeAttr("aria-describedby")},_create:function(){this._on({mouseover:"open",focusin:"open"}),
// IDs of generated tooltips, needed for destroy
this.tooltips={},
// IDs of parent tooltips where we removed the title attribute
this.parents={},this.options.disabled&&this._disable(),
// Append the aria-live region so tooltips announce correctly
this.liveRegion=t("<div>").attr({role:"log","aria-live":"assertive","aria-relevant":"additions"}).addClass("ui-helper-hidden-accessible").appendTo(this.document[0].body)},_setOption:function(i,e){var o=this;return"disabled"===i?(this[e?"_disable":"_enable"](),void(this.options[i]=e)):(this._super(i,e),void("content"===i&&t.each(this.tooltips,function(t,i){o._updateContent(i.element)})))},_disable:function(){var i=this;
// close open tooltips
t.each(this.tooltips,function(e,o){var n=t.Event("blur");n.target=n.currentTarget=o.element[0],i.close(n,!0)}),
// remove title attributes to prevent native tooltips
this.element.find(this.options.items).addBack().each(function(){var i=t(this);i.is("[title]")&&i.data("ui-tooltip-title",i.attr("title")).removeAttr("title")})},_enable:function(){
// restore title attributes
this.element.find(this.options.items).addBack().each(function(){var i=t(this);i.data("ui-tooltip-title")&&i.attr("title",i.data("ui-tooltip-title"))})},open:function(i){var e=this,o=t(i?i.target:this.element).closest(this.options.items);
// No element to show a tooltip for or the tooltip is already open
o.length&&!o.data("ui-tooltip-id")&&(o.attr("title")&&o.data("ui-tooltip-title",o.attr("title")),o.data("ui-tooltip-open",!0),
// kill parent tooltips, custom or native, for hover
i&&"mouseover"===i.type&&o.parents().each(function(){var i,o=t(this);o.data("ui-tooltip-open")&&(i=t.Event("blur"),i.target=i.currentTarget=this,e.close(i,!0)),o.attr("title")&&(o.uniqueId(),e.parents[this.id]={element:this,title:o.attr("title")},o.attr("title",""))}),this._updateContent(o,i))},_updateContent:function(t,i){var e,o=this.options.content,n=this,s=i?i.type:null;return"string"==typeof o?this._open(i,t,o):(e=o.call(t[0],function(e){
// ignore async response if tooltip was closed already
t.data("ui-tooltip-open")&&
// IE may instantly serve a cached response for ajax requests
// delay this call to _open so the other call to _open runs first
n._delay(function(){
// jQuery creates a special event for focusin when it doesn't
// exist natively. To improve performance, the native event
// object is reused and the type is changed. Therefore, we can't
// rely on the type being correct after the event finished
// bubbling, so we set it back to the previous value. (#8740)
i&&(i.type=s),this._open(i,t,e)})}),void(e&&this._open(i,t,e)))},_open:function(i,e,o){function n(t){p.of=t,l.is(":hidden")||l.position(p)}var s,l,a,r,d,p=t.extend({},this.options.position);if(o){if(s=this._find(e))return void s.tooltip.find(".ui-tooltip-content").html(o);
// if we have a title, clear it to prevent the native tooltip
// we have to check first to avoid defining a title if none exists
// (we don't want to cause an element to start matching [title])
//
// We use removeAttr only for key events, to allow IE to export the correct
// accessible attributes. For mouse events, set to empty string to avoid
// native tooltip showing up (happens only when removing inside mouseover).
e.is("[title]")&&(i&&"mouseover"===i.type?e.attr("title",""):e.removeAttr("title")),s=this._tooltip(e),l=s.tooltip,this._addDescribedBy(e,l.attr("id")),l.find(".ui-tooltip-content").html(o),
// Support: Voiceover on OS X, JAWS on IE <= 9
// JAWS announces deletions even when aria-relevant="additions"
// Voiceover will sometimes re-read the entire log region's contents from the beginning
this.liveRegion.children().hide(),o.clone?(d=o.clone(),d.removeAttr("id").find("[id]").removeAttr("id")):d=o,t("<div>").html(d).appendTo(this.liveRegion),this.options.track&&i&&/^mouse/.test(i.type)?(this._on(this.document,{mousemove:n}),
// trigger once to override element-relative positioning
n(i)):l.position(t.extend({of:e},this.options.position)),l.hide(),this._show(l,this.options.show),
// Handle tracking tooltips that are shown with a delay (#8644). As soon
// as the tooltip is visible, position the tooltip using the most recent
// event.
this.options.show&&this.options.show.delay&&(r=this.delayedShow=setInterval(function(){l.is(":visible")&&(n(p.of),clearInterval(r))},t.fx.interval)),this._trigger("open",i,{tooltip:l}),a={keyup:function(i){if(i.keyCode===t.ui.keyCode.ESCAPE){var o=t.Event(i);o.currentTarget=e[0],this.close(o,!0)}}},
// Only bind remove handler for delegated targets. Non-delegated
// tooltips will handle this in destroy.
e[0]!==this.element[0]&&(a.remove=function(){this._removeTooltip(l)}),i&&"mouseover"!==i.type||(a.mouseleave="close"),i&&"focusin"!==i.type||(a.focusout="close"),this._on(!0,e,a)}},close:function(i){var e,o=this,n=t(i?i.currentTarget:this.element),s=this._find(n);
// The tooltip may already be closed
s&&(e=s.tooltip,s.closing||(clearInterval(this.delayedShow),n.data("ui-tooltip-title")&&!n.attr("title")&&n.attr("title",n.data("ui-tooltip-title")),this._removeDescribedBy(n),s.hiding=!0,e.stop(!0),this._hide(e,this.options.hide,function(){o._removeTooltip(t(this))}),n.removeData("ui-tooltip-open"),this._off(n,"mouseleave focusout keyup"),n[0]!==this.element[0]&&this._off(n,"remove"),this._off(this.document,"mousemove"),i&&"mouseleave"===i.type&&t.each(this.parents,function(i,e){t(e.element).attr("title",e.title),delete o.parents[i]}),s.closing=!0,this._trigger("close",i,{tooltip:e}),s.hiding||(s.closing=!1)))},_tooltip:function(i){var e=t("<div>").attr("role","tooltip").addClass("ui-tooltip ui-widget ui-corner-all ui-widget-content "+(this.options.tooltipClass||"")),o=e.uniqueId().attr("id");return t("<div>").addClass("ui-tooltip-content").appendTo(e),e.appendTo(this.document[0].body),this.tooltips[o]={element:i,tooltip:e}},_find:function(t){var i=t.data("ui-tooltip-id");return i?this.tooltips[i]:null},_removeTooltip:function(t){t.remove(),delete this.tooltips[t.attr("id")]},_destroy:function(){var i=this;
// close open tooltips
t.each(this.tooltips,function(e,o){
// Delegate to close method to handle common cleanup
var n=t.Event("blur"),s=o.element;n.target=n.currentTarget=s[0],i.close(n,!0),
// Remove immediately; destroying an open tooltip doesn't use the
// hide animation
t("#"+e).remove(),
// Restore the title
s.data("ui-tooltip-title")&&(
// If the title attribute has changed since open(), don't restore
s.attr("title")||s.attr("title",s.data("ui-tooltip-title")),s.removeData("ui-tooltip-title"))}),this.liveRegion.remove()}})});