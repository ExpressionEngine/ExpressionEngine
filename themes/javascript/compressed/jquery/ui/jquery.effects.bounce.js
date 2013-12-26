/*!
 * jQuery UI Effects Bounce @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Bounce
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(e,q){e.effects.bounce=function(b){return this.queue(function(){var a=e(this),n=["position","top","bottom","left","right"],h=e.effects.setMode(a,b.options.mode||"effect"),d=b.options.direction||"up",c=b.options.distance||20,p=b.options.times||5,k=b.duration||250;/show|hide/.test(h)&&n.push("opacity");e.effects.save(a,n);a.show();e.effects.createWrapper(a);var f="up"==d||"down"==d?"top":"left",d="up"==d||"left"==d?"pos":"neg",c=b.options.distance||("top"==f?a.outerHeight({margin:!0})/3:a.outerWidth({margin:!0})/
3);"show"==h&&a.css("opacity",0).css(f,"pos"==d?-c:c);"hide"==h&&(c/=2*p);"hide"!=h&&p--;if("show"==h){var g={opacity:1};g[f]=("pos"==d?"+=":"-=")+c;a.animate(g,k/2,b.options.easing);c/=2;p--}for(g=0;g<p;g++){var l={},m={};l[f]=("pos"==d?"-=":"+=")+c;m[f]=("pos"==d?"+=":"-=")+c;a.animate(l,k/2,b.options.easing).animate(m,k/2,b.options.easing);c="hide"==h?2*c:c/2}"hide"==h?(g={opacity:0},g[f]=("pos"==d?"-=":"+=")+c,a.animate(g,k/2,b.options.easing,function(){a.hide();e.effects.restore(a,n);e.effects.removeWrapper(a);
b.callback&&b.callback.apply(this,arguments)})):(l={},m={},l[f]=("pos"==d?"-=":"+=")+c,m[f]=("pos"==d?"+=":"-=")+c,a.animate(l,k/2,b.options.easing).animate(m,k/2,b.options.easing,function(){e.effects.restore(a,n);e.effects.removeWrapper(a);b.callback&&b.callback.apply(this,arguments)}));a.queue("fx",function(){a.dequeue()});a.dequeue()})}})(jQuery);
