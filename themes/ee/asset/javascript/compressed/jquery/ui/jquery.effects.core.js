/*!
 * jQuery UI Effects @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/category/effects-core/
 */
!function(t){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery"],t):
// Browser globals
t(jQuery)}(function(t){var e="ui-effects-",
// Create a local jQuery because jQuery Color relies on it and the
// global may not exist with AMD and a custom build (#10199)
n=t;/*!
 * jQuery Color Animations v2.1.2
 * https://github.com/jquery/jquery-color
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * Date: Wed Jan 16 08:47:09 2013 -0600
 */
/******************************************************************************/
/****************************** CLASS ANIMATIONS ******************************/
/******************************************************************************/
/******************************************************************************/
/*********************************** EFFECTS **********************************/
/******************************************************************************/
/******************************************************************************/
/*********************************** EASING ***********************************/
/******************************************************************************/
return t.effects={effect:{}},function(t,e){function n(t,e,n){var r=l[e.type]||{};
// ~~ is an short way of doing floor for positive numbers
// IE will pass in empty strings as value for alpha,
// which will hit this case
return null==t?n||!e.def?null:e.def:(t=r.floor?~~t:parseFloat(t),isNaN(t)?e.def:r.mod?(t+r.mod)%r.mod:0>t?0:r.max<t?r.max:t)}function r(e){var n=f(),r=n._rgba=[];return e=e.toLowerCase(),h(u,function(t,o){var a,i=o.re.exec(e),s=i&&o.parse(i),u=o.space||"rgba";return s?(a=n[u](s),n[c[u].cache]=a[c[u].cache],r=n._rgba=a._rgba,!1):void 0}),r.length?("0,0,0,0"===r.join()&&t.extend(r,a.transparent),n):a[e]}
// hsla conversions adapted from:
// https://code.google.com/p/maashaack/source/browse/packages/graphics/trunk/src/graphics/colors/HUE2RGB.as?r=5021
function o(t,e,n){return n=(n+1)%1,1>6*n?t+(e-t)*n*6:1>2*n?e:2>3*n?t+(e-t)*(2/3-n)*6:t}var
// colors = jQuery.Color.names
a,i="backgroundColor borderBottomColor borderLeftColor borderRightColor borderTopColor color columnRuleColor outlineColor textDecorationColor textEmphasisColor",
// plusequals test for += 100 -= 100
s=/^([\-+])=\s*(\d+\.?\d*)/,
// a set of RE's that can match strings and generate color tuples.
u=[{re:/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(\d?(?:\.\d+)?)\s*)?\)/,parse:function(t){return[t[1],t[2],t[3],t[4]]}},{re:/rgba?\(\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*(?:,\s*(\d?(?:\.\d+)?)\s*)?\)/,parse:function(t){return[2.55*t[1],2.55*t[2],2.55*t[3],t[4]]}},{
// this regex ignores A-F because it's compared against an already lowercased string
re:/#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})/,parse:function(t){return[parseInt(t[1],16),parseInt(t[2],16),parseInt(t[3],16)]}},{
// this regex ignores A-F because it's compared against an already lowercased string
re:/#([a-f0-9])([a-f0-9])([a-f0-9])/,parse:function(t){return[parseInt(t[1]+t[1],16),parseInt(t[2]+t[2],16),parseInt(t[3]+t[3],16)]}},{re:/hsla?\(\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*(?:,\s*(\d?(?:\.\d+)?)\s*)?\)/,space:"hsla",parse:function(t){return[t[1],t[2]/100,t[3]/100,t[4]]}}],
// jQuery.Color( )
f=t.Color=function(e,n,r,o){return new t.Color.fn.parse(e,n,r,o)},c={rgba:{props:{red:{idx:0,type:"byte"},green:{idx:1,type:"byte"},blue:{idx:2,type:"byte"}}},hsla:{props:{hue:{idx:0,type:"degrees"},saturation:{idx:1,type:"percent"},lightness:{idx:2,type:"percent"}}}},l={"byte":{floor:!0,max:255},percent:{max:1},degrees:{mod:360,floor:!0}},d=f.support={},
// element for support tests
p=t("<p>")[0],
// local aliases of functions called often
h=t.each;
// determine rgba support immediately
p.style.cssText="background-color:rgba(1,1,1,.5)",d.rgba=p.style.backgroundColor.indexOf("rgba")>-1,
// define cache name and alpha properties
// for rgba and hsla spaces
h(c,function(t,e){e.cache="_"+t,e.props.alpha={idx:3,type:"percent",def:1}}),f.fn=t.extend(f.prototype,{parse:function(o,i,s,u){if(o===e)return this._rgba=[null,null,null,null],this;(o.jquery||o.nodeType)&&(o=t(o).css(i),i=e);var l=this,d=t.type(o),p=this._rgba=[];
// more than 1 argument specified - assume ( red, green, blue, alpha )
return i!==e&&(o=[o,i,s,u],d="array"),"string"===d?this.parse(r(o)||a._default):"array"===d?(h(c.rgba.props,function(t,e){p[e.idx]=n(o[e.idx],e)}),this):"object"===d?(o instanceof f?h(c,function(t,e){o[e.cache]&&(l[e.cache]=o[e.cache].slice())}):h(c,function(e,r){var a=r.cache;h(r.props,function(t,e){
// if the cache doesn't exist, and we know how to convert
if(!l[a]&&r.to){
// if the value was null, we don't need to copy it
// if the key was alpha, we don't need to copy it either
if("alpha"===t||null==o[t])return;l[a]=r.to(l._rgba)}
// this is the only case where we allow nulls for ALL properties.
// call clamp with alwaysAllowEmpty
l[a][e.idx]=n(o[t],e,!0)}),
// everything defined but alpha?
l[a]&&t.inArray(null,l[a].slice(0,3))<0&&(
// use the default of 1
l[a][3]=1,r.from&&(l._rgba=r.from(l[a])))}),this):void 0},is:function(t){var e=f(t),n=!0,r=this;return h(c,function(t,o){var a,i=e[o.cache];return i&&(a=r[o.cache]||o.to&&o.to(r._rgba)||[],h(o.props,function(t,e){return null!=i[e.idx]?n=i[e.idx]===a[e.idx]:void 0})),n}),n},_space:function(){var t=[],e=this;return h(c,function(n,r){e[r.cache]&&t.push(n)}),t.pop()},transition:function(t,e){var r=f(t),o=r._space(),a=c[o],i=0===this.alpha()?f("transparent"):this,s=i[a.cache]||a.to(i._rgba),u=s.slice();return r=r[a.cache],h(a.props,function(t,o){var a=o.idx,i=s[a],f=r[a],c=l[o.type]||{};null!==f&&(null===i?u[a]=f:(c.mod&&(f-i>c.mod/2?i+=c.mod:i-f>c.mod/2&&(i-=c.mod)),u[a]=n((f-i)*e+i,o)))}),this[o](u)},blend:function(e){
// if we are already opaque - return ourself
if(1===this._rgba[3])return this;var n=this._rgba.slice(),r=n.pop(),o=f(e)._rgba;return f(t.map(n,function(t,e){return(1-r)*o[e]+r*t}))},toRgbaString:function(){var e="rgba(",n=t.map(this._rgba,function(t,e){return null==t?e>2?1:0:t});return 1===n[3]&&(n.pop(),e="rgb("),e+n.join()+")"},toHslaString:function(){var e="hsla(",n=t.map(this.hsla(),function(t,e){
// catch 1 and 2
return null==t&&(t=e>2?1:0),e&&3>e&&(t=Math.round(100*t)+"%"),t});return 1===n[3]&&(n.pop(),e="hsl("),e+n.join()+")"},toHexString:function(e){var n=this._rgba.slice(),r=n.pop();return e&&n.push(~~(255*r)),"#"+t.map(n,function(t){
// default to 0 when nulls exist
return t=(t||0).toString(16),1===t.length?"0"+t:t}).join("")},toString:function(){return 0===this._rgba[3]?"transparent":this.toRgbaString()}}),f.fn.parse.prototype=f.fn,c.hsla.to=function(t){if(null==t[0]||null==t[1]||null==t[2])return[null,null,null,t[3]];var e,n,r=t[0]/255,o=t[1]/255,a=t[2]/255,i=t[3],s=Math.max(r,o,a),u=Math.min(r,o,a),f=s-u,c=s+u,l=.5*c;
// chroma (diff) == 0 means greyscale which, by definition, saturation = 0%
// otherwise, saturation is based on the ratio of chroma (diff) to lightness (add)
return e=u===s?0:r===s?60*(o-a)/f+360:o===s?60*(a-r)/f+120:60*(r-o)/f+240,n=0===f?0:.5>=l?f/c:f/(2-c),[Math.round(e)%360,n,l,null==i?1:i]},c.hsla.from=function(t){if(null==t[0]||null==t[1]||null==t[2])return[null,null,null,t[3]];var e=t[0]/360,n=t[1],r=t[2],a=t[3],i=.5>=r?r*(1+n):r+n-r*n,s=2*r-i;return[Math.round(255*o(s,i,e+1/3)),Math.round(255*o(s,i,e)),Math.round(255*o(s,i,e-1/3)),a]},h(c,function(r,o){var a=o.props,i=o.cache,u=o.to,c=o.from;
// makes rgba() and hsla()
f.fn[r]=function(r){if(
// generate a cache for this space if it doesn't exist
u&&!this[i]&&(this[i]=u(this._rgba)),r===e)return this[i].slice();var o,s=t.type(r),l="array"===s||"object"===s?r:arguments,d=this[i].slice();return h(a,function(t,e){var r=l["object"===s?t:e.idx];null==r&&(r=d[e.idx]),d[e.idx]=n(r,e)}),c?(o=f(c(d)),o[i]=d,o):f(d)},
// makes red() green() blue() alpha() hue() saturation() lightness()
h(a,function(e,n){
// alpha is included in more than one space
f.fn[e]||(f.fn[e]=function(o){var a,i=t.type(o),u="alpha"===e?this._hsla?"hsla":"rgba":r,f=this[u](),c=f[n.idx];return"undefined"===i?c:("function"===i&&(o=o.call(this,c),i=t.type(o)),null==o&&n.empty?this:("string"===i&&(a=s.exec(o),a&&(o=c+parseFloat(a[2])*("+"===a[1]?1:-1))),f[n.idx]=o,this[u](f)))})})}),
// add cssHook and .fx.step function for each named hook.
// accept a space separated string of properties
f.hook=function(e){var n=e.split(" ");h(n,function(e,n){t.cssHooks[n]={set:function(e,o){var a,i,s="";if("transparent"!==o&&("string"!==t.type(o)||(a=r(o)))){if(o=f(a||o),!d.rgba&&1!==o._rgba[3]){for(i="backgroundColor"===n?e.parentNode:e;(""===s||"transparent"===s)&&i&&i.style;)try{s=t.css(i,"backgroundColor"),i=i.parentNode}catch(u){}o=o.blend(s&&"transparent"!==s?s:"_default")}o=o.toRgbaString()}try{e.style[n]=o}catch(u){}}},t.fx.step[n]=function(e){e.colorInit||(e.start=f(e.elem,n),e.end=f(e.end),e.colorInit=!0),t.cssHooks[n].set(e.elem,e.start.transition(e.end,e.pos))}})},f.hook(i),t.cssHooks.borderColor={expand:function(t){var e={};return h(["Top","Right","Bottom","Left"],function(n,r){e["border"+r+"Color"]=t}),e}},
// Basic color names only.
// Usage of any of the other color names requires adding yourself or including
// jquery.color.svg-names.js.
a=t.Color.names={
// 4.1. Basic color keywords
aqua:"#00ffff",black:"#000000",blue:"#0000ff",fuchsia:"#ff00ff",gray:"#808080",green:"#008000",lime:"#00ff00",maroon:"#800000",navy:"#000080",olive:"#808000",purple:"#800080",red:"#ff0000",silver:"#c0c0c0",teal:"#008080",white:"#ffffff",yellow:"#ffff00",
// 4.2.3. "transparent" color keyword
transparent:[null,null,null,0],_default:"#ffffff"}}(n),function(){function e(e){var n,r,o=e.ownerDocument.defaultView?e.ownerDocument.defaultView.getComputedStyle(e,null):e.currentStyle,a={};if(o&&o.length&&o[0]&&o[o[0]])for(r=o.length;r--;)n=o[r],"string"==typeof o[n]&&(a[t.camelCase(n)]=o[n]);else for(n in o)"string"==typeof o[n]&&(a[n]=o[n]);return a}function r(e,n){var r,o,i={};for(r in n)o=n[r],e[r]!==o&&(a[r]||(t.fx.step[r]||!isNaN(parseFloat(o)))&&(i[r]=o));return i}var o=["add","remove","toggle"],a={border:1,borderBottom:1,borderColor:1,borderLeft:1,borderRight:1,borderTop:1,borderWidth:1,margin:1,padding:1};t.each(["borderLeftStyle","borderRightStyle","borderBottomStyle","borderTopStyle"],function(e,r){t.fx.step[r]=function(t){("none"!==t.end&&!t.setAttr||1===t.pos&&!t.setAttr)&&(n.style(t.elem,r,t.end),t.setAttr=!0)}}),
// support: jQuery <1.8
t.fn.addBack||(t.fn.addBack=function(t){return this.add(null==t?this.prevObject:this.prevObject.filter(t))}),t.effects.animateClass=function(n,a,i,s){var u=t.speed(a,i,s);return this.queue(function(){var a,i=t(this),s=i.attr("class")||"",f=u.children?i.find("*").addBack():i;f=f.map(function(){var n=t(this);return{el:n,start:e(this)}}),a=function(){t.each(o,function(t,e){n[e]&&i[e+"Class"](n[e])})},a(),f=f.map(function(){return this.end=e(this.el[0]),this.diff=r(this.start,this.end),this}),i.attr("class",s),f=f.map(function(){var e=this,n=t.Deferred(),r=t.extend({},u,{queue:!1,complete:function(){n.resolve(e)}});return this.el.animate(this.diff,r),n.promise()}),t.when.apply(t,f.get()).done(function(){a(),t.each(arguments,function(){var e=this.el;t.each(this.diff,function(t){e.css(t,"")})}),u.complete.call(i[0])})})},t.fn.extend({addClass:function(e){return function(n,r,o,a){return r?t.effects.animateClass.call(this,{add:n},r,o,a):e.apply(this,arguments)}}(t.fn.addClass),removeClass:function(e){return function(n,r,o,a){return arguments.length>1?t.effects.animateClass.call(this,{remove:n},r,o,a):e.apply(this,arguments)}}(t.fn.removeClass),toggleClass:function(e){return function(n,r,o,a,i){return"boolean"==typeof r||void 0===r?o?t.effects.animateClass.call(this,r?{add:n}:{remove:n},o,a,i):e.apply(this,arguments):t.effects.animateClass.call(this,{toggle:n},r,o,a)}}(t.fn.toggleClass),switchClass:function(e,n,r,o,a){return t.effects.animateClass.call(this,{add:n,remove:e},r,o,a)}})}(),function(){
// return an effect options object for the given parameters:
function n(e,n,r,o){
// allow passing all options as the first parameter
// convert to an object
// catch (effect, null, ...)
// catch (effect, callback)
// catch (effect, speed, ?)
// catch (effect, options, callback)
// add options to effect
return t.isPlainObject(e)&&(n=e,e=e.effect),e={effect:e},null==n&&(n={}),t.isFunction(n)&&(o=n,r=null,n={}),("number"==typeof n||t.fx.speeds[n])&&(o=r,r=n,n={}),t.isFunction(r)&&(o=r,r=null),n&&t.extend(e,n),r=r||n.duration,e.duration=t.fx.off?0:"number"==typeof r?r:r in t.fx.speeds?t.fx.speeds[r]:t.fx.speeds._default,e.complete=o||n.complete,e}function r(e){
// Valid standard speeds (nothing, number, named speed)
// Valid standard speeds (nothing, number, named speed)
// Invalid strings - treat as "normal" speed
// Complete callback
// Options hash (but not naming an effect)
return!e||"number"==typeof e||t.fx.speeds[e]?!0:"string"!=typeof e||t.effects.effect[e]?t.isFunction(e)?!0:"object"!=typeof e||e.effect?!1:!0:!0}t.extend(t.effects,{version:"@VERSION",
// Saves a set of properties in a data storage
save:function(t,n){for(var r=0;r<n.length;r++)null!==n[r]&&t.data(e+n[r],t[0].style[n[r]])},
// Restores a set of previously saved properties from a data storage
restore:function(t,n){var r,o;for(o=0;o<n.length;o++)null!==n[o]&&(r=t.data(e+n[o]),void 0===r&&(r=""),t.css(n[o],r))},setMode:function(t,e){return"toggle"===e&&(e=t.is(":hidden")?"show":"hide"),e},
// Translates a [top,left] array into a baseline value
// this should be a little more flexible in the future to handle a string & hash
getBaseline:function(t,e){var n,r;switch(t[0]){case"top":n=0;break;case"middle":n=.5;break;case"bottom":n=1;break;default:n=t[0]/e.height}switch(t[1]){case"left":r=0;break;case"center":r=.5;break;case"right":r=1;break;default:r=t[1]/e.width}return{x:r,y:n}},
// Wraps the element around a wrapper that copies position properties
createWrapper:function(e){
// if the element is already wrapped, return it
if(e.parent().is(".ui-effects-wrapper"))return e.parent();
// wrap the element
var n={width:e.outerWidth(!0),height:e.outerHeight(!0),"float":e.css("float")},r=t("<div></div>").addClass("ui-effects-wrapper").css({fontSize:"100%",background:"transparent",border:"none",margin:0,padding:0}),
// Store the size in case width/height are defined in % - Fixes #5245
o={width:e.width(),height:e.height()},a=document.activeElement;
// support: Firefox
// Firefox incorrectly exposes anonymous content
// https://bugzilla.mozilla.org/show_bug.cgi?id=561664
try{a.id}catch(i){a=document.body}
// Fixes #7595 - Elements lose focus when wrapped.
//Hotfix for jQuery 1.4 since some change in wrap() seems to actually lose the reference to the wrapped element
// transfer positioning properties to the wrapper
return e.wrap(r),(e[0]===a||t.contains(e[0],a))&&t(a).focus(),r=e.parent(),"static"===e.css("position")?(r.css({position:"relative"}),e.css({position:"relative"})):(t.extend(n,{position:e.css("position"),zIndex:e.css("z-index")}),t.each(["top","left","bottom","right"],function(t,r){n[r]=e.css(r),isNaN(parseInt(n[r],10))&&(n[r]="auto")}),e.css({position:"relative",top:0,left:0,right:"auto",bottom:"auto"})),e.css(o),r.css(n).show()},removeWrapper:function(e){var n=document.activeElement;
// Fixes #7595 - Elements lose focus when wrapped.
return e.parent().is(".ui-effects-wrapper")&&(e.parent().replaceWith(e),(e[0]===n||t.contains(e[0],n))&&t(n).focus()),e},setTransition:function(e,n,r,o){return o=o||{},t.each(n,function(t,n){var a=e.cssUnit(n);a[0]>0&&(o[n]=a[0]*r+a[1])}),o}}),t.fn.extend({effect:function(){function e(e){function n(){t.isFunction(a)&&a.call(o[0]),t.isFunction(e)&&e()}var o=t(this),a=r.complete,s=r.mode;
// If the element already has the correct final state, delegate to
// the core methods so the internal tracking of "olddisplay" works.
(o.is(":hidden")?"hide"===s:"show"===s)?(o[s](),n()):i.call(o[0],r,n)}var r=n.apply(this,arguments),o=r.mode,a=r.queue,i=t.effects.effect[r.effect];
// delegate to the original method (e.g., .show()) if possible
return t.fx.off||!i?o?this[o](r.duration,r.complete):this.each(function(){r.complete&&r.complete.call(this)}):a===!1?this.each(e):this.queue(a||"fx",e)},show:function(t){return function(e){if(r(e))return t.apply(this,arguments);var o=n.apply(this,arguments);return o.mode="show",this.effect.call(this,o)}}(t.fn.show),hide:function(t){return function(e){if(r(e))return t.apply(this,arguments);var o=n.apply(this,arguments);return o.mode="hide",this.effect.call(this,o)}}(t.fn.hide),toggle:function(t){return function(e){if(r(e)||"boolean"==typeof e)return t.apply(this,arguments);var o=n.apply(this,arguments);return o.mode="toggle",this.effect.call(this,o)}}(t.fn.toggle),
// helper functions
cssUnit:function(e){var n=this.css(e),r=[];return t.each(["em","px","%","pt"],function(t,e){n.indexOf(e)>0&&(r=[parseFloat(n),e])}),r}})}(),function(){
// based on easing equations from Robert Penner (http://www.robertpenner.com/easing)
var e={};t.each(["Quad","Cubic","Quart","Quint","Expo"],function(t,n){e[n]=function(e){return Math.pow(e,t+2)}}),t.extend(e,{Sine:function(t){return 1-Math.cos(t*Math.PI/2)},Circ:function(t){return 1-Math.sqrt(1-t*t)},Elastic:function(t){return 0===t||1===t?t:-Math.pow(2,8*(t-1))*Math.sin((80*(t-1)-7.5)*Math.PI/15)},Back:function(t){return t*t*(3*t-2)},Bounce:function(t){for(var e,n=4;t<((e=Math.pow(2,--n))-1)/11;);return 1/Math.pow(4,3-n)-7.5625*Math.pow((3*e-2)/22-t,2)}}),t.each(e,function(e,n){t.easing["easeIn"+e]=n,t.easing["easeOut"+e]=function(t){return 1-n(1-t)},t.easing["easeInOut"+e]=function(t){return.5>t?n(2*t)/2:1-n(-2*t+2)/2}})}(),t.effects});