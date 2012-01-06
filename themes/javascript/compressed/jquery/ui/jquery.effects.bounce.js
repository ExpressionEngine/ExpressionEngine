/*
 * jQuery UI Effects Bounce 1.8.16
 *
 * Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Bounce
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(e){e.effects.bounce=function(b){return this.queue(function(){var a=e(this),l=["position","top","bottom","left","right"],h=e.effects.setMode(a,b.options.mode||"effect"),d=b.options.direction||"up",c=b.options.distance||20,m=b.options.times||5,i=b.duration||250;/show|hide/.test(h)&&l.push("opacity");e.effects.save(a,l);a.show();e.effects.createWrapper(a);var f="up"==d||"down"==d?"top":"left",d="up"==d||"left"==d?"pos":"neg",c=b.options.distance||("top"==f?a.outerHeight({margin:!0})/3:a.outerWidth({margin:!0})/
3);"show"==h&&a.css("opacity",0).css(f,"pos"==d?-c:c);"hide"==h&&(c/=2*m);"hide"!=h&&m--;if("show"==h){var g={opacity:1};g[f]=("pos"==d?"+=":"-=")+c;a.animate(g,i/2,b.options.easing);c/=2;m--}for(g=0;g<m;g++){var j={},k={};j[f]=("pos"==d?"-=":"+=")+c;k[f]=("pos"==d?"+=":"-=")+c;a.animate(j,i/2,b.options.easing).animate(k,i/2,b.options.easing);c="hide"==h?2*c:c/2}"hide"==h?(g={opacity:0},g[f]=("pos"==d?"-=":"+=")+c,a.animate(g,i/2,b.options.easing,function(){a.hide();e.effects.restore(a,l);e.effects.removeWrapper(a);
b.callback&&b.callback.apply(this,arguments)})):(j={},k={},j[f]=("pos"==d?"-=":"+=")+c,k[f]=("pos"==d?"+=":"-=")+c,a.animate(j,i/2,b.options.easing).animate(k,i/2,b.options.easing,function(){e.effects.restore(a,l);e.effects.removeWrapper(a);b.callback&&b.callback.apply(this,arguments)}));a.queue("fx",function(){a.dequeue()});a.dequeue()})}})(jQuery);
