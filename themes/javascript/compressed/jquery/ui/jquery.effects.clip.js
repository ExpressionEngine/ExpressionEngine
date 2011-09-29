/*
 * jQuery UI Effects Clip 1.8.16
 *
 * Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Clip
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(b){b.effects.clip=function(e){return this.queue(function(){var a=b(this),h="position,top,bottom,left,right,height,width".split(","),f=b.effects.setMode(a,e.options.mode||"hide"),c=e.options.direction||"vertical";b.effects.save(a,h);a.show();var d=b.effects.createWrapper(a).css({overflow:"hidden"}),d=a[0].tagName=="IMG"?d:a,i=c=="vertical"?"height":"width",j=c=="vertical"?"top":"left",c=c=="vertical"?d.height():d.width();f=="show"&&(d.css(i,0),d.css(j,c/2));var g={};g[i]=f=="show"?c:0;g[j]=
f=="show"?0:c/2;d.animate(g,{queue:!1,duration:e.duration,easing:e.options.easing,complete:function(){f=="hide"&&a.hide();b.effects.restore(a,h);b.effects.removeWrapper(a);e.callback&&e.callback.apply(a[0],arguments);a.dequeue()}})})}})(jQuery);
