/*!
 * jQuery UI Effects Slide @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/slide-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.slide=function(t,f){
// Create element
var i,o=e(this),n=["position","top","bottom","left","right","width","height"],s=e.effects.setMode(o,t.mode||"show"),r="show"===s,c=t.direction||"left",d="up"===c||"down"===c?"top":"left",u="up"===c||"left"===c,a={};
// Adjust
e.effects.save(o,n),o.show(),i=t.distance||o["top"===d?"outerHeight":"outerWidth"](!0),e.effects.createWrapper(o).css({overflow:"hidden"}),r&&o.css(d,u?isNaN(i)?"-"+i:-i:i),
// Animation
a[d]=(r?u?"+=":"-=":u?"-=":"+=")+i,
// Animate
o.animate(a,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){"hide"===s&&o.hide(),e.effects.restore(o,n),e.effects.removeWrapper(o),f()}})}});