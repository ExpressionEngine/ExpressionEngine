/*!
 * jQuery UI Effects Blind @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Blind
 *
 * Depends:
 *	jquery.effects.core.js
 */
!function(e){e.effects.blind=function(t){return this.queue(function(){var i=e(this),o=["position","top","bottom","left","right"],s=e.effects.setMode(i,t.options.mode||"hide"),r=t.options.direction||"vertical";e.effects.save(i,o),i.show();var c=e.effects.createWrapper(i).css({overflow:"hidden"}),a="vertical"==r?"height":"width",n="vertical"==r?c.height():c.width();"show"==s&&c.css(a,0);var f={};f[a]="show"==s?n:0,c.animate(f,t.duration,t.options.easing,function(){"hide"==s&&i.hide(),e.effects.restore(i,o),e.effects.removeWrapper(i),t.callback&&t.callback.apply(i[0],arguments),i.dequeue()})})}}(jQuery);