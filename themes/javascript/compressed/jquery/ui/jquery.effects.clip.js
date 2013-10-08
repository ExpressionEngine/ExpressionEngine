/*!
 * jQuery UI Effects Clip @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Clip
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(b,m){b.effects.clip=function(e){return this.queue(function(){var a=b(this),h="position top bottom left right height width".split(" "),f=b.effects.setMode(a,e.options.mode||"hide"),c=e.options.direction||"vertical";b.effects.save(a,h);a.show();var d=b.effects.createWrapper(a).css({overflow:"hidden"}),d="IMG"==a[0].tagName?d:a,k="vertical"==c?"height":"width",l="vertical"==c?"top":"left",c="vertical"==c?d.height():d.width();"show"==f&&(d.css(k,0),d.css(l,c/2));var g={};g[k]="show"==f?c:0;
g[l]="show"==f?0:c/2;d.animate(g,{queue:!1,duration:e.duration,easing:e.options.easing,complete:function(){"hide"==f&&a.hide();b.effects.restore(a,h);b.effects.removeWrapper(a);e.callback&&e.callback.apply(a[0],arguments);a.dequeue()}})})}})(jQuery);
