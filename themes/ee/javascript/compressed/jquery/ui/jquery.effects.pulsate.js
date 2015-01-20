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
!function(e){e.effects.pulsate=function(i){return this.queue(function(){var t=e(this),o=e.effects.setMode(t,i.options.mode||"show"),s=2*(i.options.times||5)-1,n=i.duration?i.duration/2:e.fx.speeds._default/2,a=t.is(":visible"),u=0;a||(t.css("opacity",0).show(),u=1),("hide"==o&&a||"show"==o&&!a)&&s--;for(var c=0;s>c;c++)t.animate({opacity:u},n,i.options.easing),u=(u+1)%2;t.animate({opacity:u},n,i.options.easing,function(){0==u&&t.hide(),i.callback&&i.callback.apply(this,arguments)}),t.queue("fx",function(){t.dequeue()}).dequeue()})}}(jQuery);