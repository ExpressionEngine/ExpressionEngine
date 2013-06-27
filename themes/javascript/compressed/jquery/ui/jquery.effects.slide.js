/*!
 * jQuery UI Effects Slide @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Slide
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(c,l){c.effects.slide=function(d){return this.queue(function(){var a=c(this),h=["position","top","bottom","left","right"],f=c.effects.setMode(a,d.options.mode||"show"),b=d.options.direction||"left";c.effects.save(a,h);a.show();c.effects.createWrapper(a).css({overflow:"hidden"});var g="up"==b||"down"==b?"top":"left",b="up"==b||"left"==b?"pos":"neg",e=d.options.distance||("top"==g?a.outerHeight({margin:!0}):a.outerWidth({margin:!0}));"show"==f&&a.css(g,"pos"==b?isNaN(e)?"-"+e:-e:e);var k={};
k[g]=("show"==f?"pos"==b?"+=":"-=":"pos"==b?"-=":"+=")+e;a.animate(k,{queue:!1,duration:d.duration,easing:d.options.easing,complete:function(){"hide"==f&&a.hide();c.effects.restore(a,h);c.effects.removeWrapper(a);d.callback&&d.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
