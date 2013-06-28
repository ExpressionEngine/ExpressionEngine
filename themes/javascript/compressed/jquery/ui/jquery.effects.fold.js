/*!
 * jQuery UI Effects Fold @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Fold
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(c,p){c.effects.fold=function(a){return this.queue(function(){var b=c(this),l=["position","top","bottom","left","right"],d=c.effects.setMode(b,a.options.mode||"hide"),g=a.options.size||15,h=!!a.options.horizFirst,m=a.duration?a.duration/2:c.fx.speeds._default/2;c.effects.save(b,l);b.show();var e=c.effects.createWrapper(b).css({overflow:"hidden"}),f="show"==d!=h,n=f?["width","height"]:["height","width"],f=f?[e.width(),e.height()]:[e.height(),e.width()],k=/([0-9]+)%/.exec(g);k&&(g=parseInt(k[1],
10)/100*f["hide"==d?0:1]);"show"==d&&e.css(h?{height:0,width:g}:{height:g,width:0});h={};k={};h[n[0]]="show"==d?f[0]:g;k[n[1]]="show"==d?f[1]:0;e.animate(h,m,a.options.easing).animate(k,m,a.options.easing,function(){"hide"==d&&b.hide();c.effects.restore(b,l);c.effects.removeWrapper(b);a.callback&&a.callback.apply(b[0],arguments);b.dequeue()})})}})(jQuery);
