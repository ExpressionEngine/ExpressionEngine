/*!
 * jQuery UI Effects Scale @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/scale-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect","./effect-size"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.scale=function(t,i){
// Create element
var o=e(this),h=e.extend(!0,{},t),f=e.effects.setMode(o,t.mode||"effect"),r=parseInt(t.percent,10)||(0===parseInt(t.percent,10)?0:"hide"===f?0:100),c=t.direction||"both",d=t.origin,n={height:o.height(),width:o.width(),outerHeight:o.outerHeight(),outerWidth:o.outerWidth()},u={y:"horizontal"!==c?r/100:1,x:"vertical"!==c?r/100:1};
// We are going to pass this effect to the size effect:
h.effect="size",h.queue=!1,h.complete=i,
// Set default origin and restore for show/hide
"effect"!==f&&(h.origin=d||["middle","center"],h.restore=!0),h.from=t.from||("show"===f?{height:0,width:0,outerHeight:0,outerWidth:0}:n),h.to={height:n.height*u.y,width:n.width*u.x,outerHeight:n.outerHeight*u.y,outerWidth:n.outerWidth*u.x},
// Fade option to support puff
h.fade&&("show"===f&&(h.from.opacity=0,h.to.opacity=1),"hide"===f&&(h.from.opacity=1,h.to.opacity=0)),
// Animate
o.effect(h)}});