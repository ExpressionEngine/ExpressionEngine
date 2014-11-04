/*!
 * jQuery UI Effects Drop @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Drop
 *
 * Depends:
 *	jquery.effects.core.js
 */
!function(e){e.effects.drop=function(t){return this.queue(function(){var o=e(this),s=["position","top","bottom","left","right","opacity"],i=e.effects.setMode(o,t.options.mode||"hide"),n=t.options.direction||"left";e.effects.save(o,s),o.show(),e.effects.createWrapper(o);var a="up"==n||"down"==n?"top":"left",p="up"==n||"left"==n?"pos":"neg",r=t.options.distance||("top"==a?o.outerHeight({margin:!0})/2:o.outerWidth({margin:!0})/2);"show"==i&&o.css("opacity",0).css(a,"pos"==p?-r:r);var c={opacity:"show"==i?1:0};c[a]=("show"==i?"pos"==p?"+=":"-=":"pos"==p?"-=":"+=")+r,o.animate(c,{queue:!1,duration:t.duration,easing:t.options.easing,complete:function(){"hide"==i&&o.hide(),e.effects.restore(o,s),e.effects.removeWrapper(o),t.callback&&t.callback.apply(this,arguments),o.dequeue()}})})}}(jQuery);