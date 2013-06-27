/*!
 * jQuery UI Effects Pulsate @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Pulsate
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(e,k){e.effects.pulsate=function(a){return this.queue(function(){var b=e(this),d=e.effects.setMode(b,a.options.mode||"show"),g=2*(a.options.times||5)-1,h=a.duration?a.duration/2:e.fx.speeds._default/2,f=b.is(":visible"),c=0;f||(b.css("opacity",0).show(),c=1);("hide"==d&&f||"show"==d&&!f)&&g--;for(d=0;d<g;d++)b.animate({opacity:c},h,a.options.easing),c=(c+1)%2;b.animate({opacity:c},h,a.options.easing,function(){0==c&&b.hide();a.callback&&a.callback.apply(this,arguments)});b.queue("fx",function(){b.dequeue()}).dequeue()})}})(jQuery);
