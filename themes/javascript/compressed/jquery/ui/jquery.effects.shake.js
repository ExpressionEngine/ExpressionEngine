/*!
 * jQuery UI Effects Shake @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Shake
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(d,p){d.effects.shake=function(a){return this.queue(function(){var b=d(this),l=["position","top","bottom","left","right"];d.effects.setMode(b,a.options.mode||"effect");var c=a.options.direction||"left",e=a.options.distance||20,n=a.options.times||3,f=a.duration||a.options.duration||140;d.effects.save(b,l);b.show();d.effects.createWrapper(b);var g="up"==c||"down"==c?"top":"left",h="up"==c||"left"==c?"pos":"neg",c={},k={},m={};c[g]=("pos"==h?"-=":"+=")+e;k[g]=("pos"==h?"+=":"-=")+2*e;m[g]=("pos"==
h?"-=":"+=")+2*e;b.animate(c,f,a.options.easing);for(e=1;e<n;e++)b.animate(k,f,a.options.easing).animate(m,f,a.options.easing);b.animate(k,f,a.options.easing).animate(c,f/2,a.options.easing,function(){d.effects.restore(b,l);d.effects.removeWrapper(b);a.callback&&a.callback.apply(this,arguments)});b.queue("fx",function(){b.dequeue()});b.dequeue()})}})(jQuery);
