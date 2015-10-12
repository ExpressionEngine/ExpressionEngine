/*!
 * jQuery UI Effects Pulsate @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/pulsate-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.pulsate=function(i,t){var n,f=e(this),c=e.effects.setMode(f,i.mode||"show"),o="show"===c,s="hide"===c,u=o||"hide"===c,
// showing or hiding leaves of the "last" animation
a=2*(i.times||5)+(u?1:0),d=i.duration/a,p=0,h=f.queue(),r=h.length;
// anims - 1 opacity "toggles"
for((o||!f.is(":visible"))&&(f.css("opacity",0).show(),p=1),n=1;a>n;n++)f.animate({opacity:p},d,i.easing),p=1-p;f.animate({opacity:p},d,i.easing),f.queue(function(){s&&f.hide(),t()}),
// We just queued up "anims" animations, we need to put them next in the queue
r>1&&h.splice.apply(h,[1,0].concat(h.splice(r,a+1))),f.dequeue()}});