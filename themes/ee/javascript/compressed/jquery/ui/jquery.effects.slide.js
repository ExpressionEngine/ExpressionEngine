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
!function(e){e.effects.slide=function(t){return this.queue(function(){var o=e(this),s=["position","top","bottom","left","right"],i=e.effects.setMode(o,t.options.mode||"show"),n=t.options.direction||"left";e.effects.save(o,s),o.show(),e.effects.createWrapper(o).css({overflow:"hidden"});var r="up"==n||"down"==n?"top":"left",a="up"==n||"left"==n?"pos":"neg",f=t.options.distance||("top"==r?o.outerHeight({margin:!0}):o.outerWidth({margin:!0}));"show"==i&&o.css(r,"pos"==a?isNaN(f)?"-"+f:-f:f);var p={};p[r]=("show"==i?"pos"==a?"+=":"-=":"pos"==a?"-=":"+=")+f,o.animate(p,{queue:!1,duration:t.duration,easing:t.options.easing,complete:function(){"hide"==i&&o.hide(),e.effects.restore(o,s),e.effects.removeWrapper(o),t.callback&&t.callback.apply(this,arguments),o.dequeue()}})})}}(jQuery);