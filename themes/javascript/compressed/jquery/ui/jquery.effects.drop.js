/*
 * jQuery UI Effects Drop 1.8.16
 *
 * Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Drop
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(c){c.effects.drop=function(d){return this.queue(function(){var a=c(this),h="position,top,bottom,left,right,opacity".split(","),e=c.effects.setMode(a,d.options.mode||"hide"),b=d.options.direction||"left";c.effects.save(a,h);a.show();c.effects.createWrapper(a);var f="up"==b||"down"==b?"top":"left",b="up"==b||"left"==b?"pos":"neg",g=d.options.distance||("top"==f?a.outerHeight({margin:!0})/2:a.outerWidth({margin:!0})/2);"show"==e&&a.css("opacity",0).css(f,"pos"==b?-g:g);var i={opacity:"show"==
e?1:0};i[f]=("show"==e?"pos"==b?"+=":"-=":"pos"==b?"-=":"+=")+g;a.animate(i,{queue:!1,duration:d.duration,easing:d.options.easing,complete:function(){"hide"==e&&a.hide();c.effects.restore(a,h);c.effects.removeWrapper(a);d.callback&&d.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
