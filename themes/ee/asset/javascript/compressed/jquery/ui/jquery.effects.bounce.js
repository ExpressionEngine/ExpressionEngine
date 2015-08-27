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
!function(e){e.effects.bounce=function(t){return this.queue(function(){var o=e(this),i=["position","top","bottom","left","right"],s=e.effects.setMode(o,t.options.mode||"effect"),a=t.options.direction||"up",n=t.options.distance||20,p=t.options.times||5,c=t.duration||250;/show|hide/.test(s)&&i.push("opacity"),e.effects.save(o,i),o.show(),e.effects.createWrapper(o);var r="up"==a||"down"==a?"top":"left",f="up"==a||"left"==a?"pos":"neg",n=t.options.distance||("top"==r?o.outerHeight({margin:!0})/3:o.outerWidth({margin:!0})/3);if("show"==s&&o.css("opacity",0).css(r,"pos"==f?-n:n),"hide"==s&&(n/=2*p),"hide"!=s&&p--,"show"==s){var u={opacity:1};u[r]=("pos"==f?"+=":"-=")+n,o.animate(u,c/2,t.options.easing),n/=2,p--}for(var h=0;p>h;h++){var d={},m={};d[r]=("pos"==f?"-=":"+=")+n,m[r]=("pos"==f?"+=":"-=")+n,o.animate(d,c/2,t.options.easing).animate(m,c/2,t.options.easing),n="hide"==s?2*n:n/2}if("hide"==s){var u={opacity:0};u[r]=("pos"==f?"-=":"+=")+n,o.animate(u,c/2,t.options.easing,function(){o.hide(),e.effects.restore(o,i),e.effects.removeWrapper(o),t.callback&&t.callback.apply(this,arguments)})}else{var d={},m={};d[r]=("pos"==f?"-=":"+=")+n,m[r]=("pos"==f?"+=":"-=")+n,o.animate(d,c/2,t.options.easing).animate(m,c/2,t.options.easing,function(){e.effects.restore(o,i),e.effects.removeWrapper(o),t.callback&&t.callback.apply(this,arguments)})}o.queue("fx",function(){o.dequeue()}),o.dequeue()})}}(jQuery);