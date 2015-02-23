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
!function(e){e.effects.shake=function(t){return this.queue(function(){var o=e(this),n=["position","top","bottom","left","right"],i=(e.effects.setMode(o,t.options.mode||"effect"),t.options.direction||"left"),s=t.options.distance||20,a=t.options.times||3,f=t.duration||t.options.duration||140;e.effects.save(o,n),o.show(),e.effects.createWrapper(o);var p="up"==i||"down"==i?"top":"left",r="up"==i||"left"==i?"pos":"neg",u={},c={},m={};u[p]=("pos"==r?"-=":"+=")+s,c[p]=("pos"==r?"+=":"-=")+2*s,m[p]=("pos"==r?"-=":"+=")+2*s,o.animate(u,f,t.options.easing);for(var d=1;a>d;d++)o.animate(c,f,t.options.easing).animate(m,f,t.options.easing);o.animate(c,f,t.options.easing).animate(u,f/2,t.options.easing,function(){e.effects.restore(o,n),e.effects.removeWrapper(o),t.callback&&t.callback.apply(this,arguments)}),o.queue("fx",function(){o.dequeue()}),o.dequeue()})}}(jQuery);