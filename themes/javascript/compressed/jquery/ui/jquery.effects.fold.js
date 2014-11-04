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
!function(e){e.effects.fold=function(t){return this.queue(function(){var i=e(this),o=["position","top","bottom","left","right"],s=e.effects.setMode(i,t.options.mode||"hide"),h=t.options.size||15,n=!!t.options.horizFirst,a=t.duration?t.duration/2:e.fx.speeds._default/2;e.effects.save(i,o),i.show();var r=e.effects.createWrapper(i).css({overflow:"hidden"}),f="show"==s!=n,d=f?["width","height"]:["height","width"],c=f?[r.width(),r.height()]:[r.height(),r.width()],p=/([0-9]+)%/.exec(h);p&&(h=parseInt(p[1],10)/100*c["hide"==s?0:1]),"show"==s&&r.css(n?{height:0,width:h}:{height:h,width:0});var u={},w={};u[d[0]]="show"==s?c[0]:h,w[d[1]]="show"==s?c[1]:0,r.animate(u,a,t.options.easing).animate(w,a,t.options.easing,function(){"hide"==s&&i.hide(),e.effects.restore(i,o),e.effects.removeWrapper(i),t.callback&&t.callback.apply(i[0],arguments),i.dequeue()})})}}(jQuery);