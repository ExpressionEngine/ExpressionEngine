/*!
 * jQuery UI Effects Scale @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Scale
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(c,v){c.effects.puff=function(b){return this.queue(function(){var a=c(this),d=c.effects.setMode(a,b.options.mode||"hide"),e=parseInt(b.options.percent,10)||150,k=e/100,g={height:a.height(),width:a.width()};c.extend(b.options,{fade:!0,mode:d,percent:"hide"==d?e:100,from:"hide"==d?g:{height:g.height*k,width:g.width*k}});a.effect("scale",b.options,b.duration,b.callback);a.dequeue()})};c.effects.scale=function(b){return this.queue(function(){var a=c(this),d=c.extend(!0,{},b.options),e=c.effects.setMode(a,
b.options.mode||"effect"),k=parseInt(b.options.percent,10)||(0==parseInt(b.options.percent,10)?0:"hide"==e?0:100),g=b.options.direction||"both",f=b.options.origin;"effect"!=e&&(d.origin=f||["middle","center"],d.restore=!0);f={height:a.height(),width:a.width()};a.from=b.options.from||("show"==e?{height:0,width:0}:f);a.to={height:f.height*("horizontal"!=g?k/100:1),width:f.width*("vertical"!=g?k/100:1)};b.options.fade&&("show"==e&&(a.from.opacity=0,a.to.opacity=1),"hide"==e&&(a.from.opacity=1,a.to.opacity=
0));d.from=a.from;d.to=a.to;d.mode=e;a.effect("size",d,b.duration,b.callback);a.dequeue()})};c.effects.size=function(b){return this.queue(function(){var a=c(this),d="position top bottom left right width height overflow opacity".split(" "),e="position top bottom left right overflow opacity".split(" "),k=["width","height","overflow"],g=["fontSize"],f=["borderTopWidth","borderBottomWidth","paddingTop","paddingBottom"],l=["borderLeftWidth","borderRightWidth","paddingLeft","paddingRight"],u=c.effects.setMode(a,
b.options.mode||"effect"),t=b.options.restore||!1,q=b.options.scale||"both",m=b.options.origin,h={height:a.height(),width:a.width()};a.from=b.options.from||h;a.to=b.options.to||h;m&&(m=c.effects.getBaseline(m,h),a.from.top=(h.height-a.from.height)*m.y,a.from.left=(h.width-a.from.width)*m.x,a.to.top=(h.height-a.to.height)*m.y,a.to.left=(h.width-a.to.width)*m.x);var n=a.from.height/h.height,r=a.from.width/h.width,p=a.to.height/h.height,s=a.to.width/h.width;if("box"==q||"both"==q)n!=p&&(d=d.concat(f),
a.from=c.effects.setTransition(a,f,n,a.from),a.to=c.effects.setTransition(a,f,p,a.to)),r!=s&&(d=d.concat(l),a.from=c.effects.setTransition(a,l,r,a.from),a.to=c.effects.setTransition(a,l,s,a.to));"content"!=q&&"both"!=q||n==p||(d=d.concat(g),a.from=c.effects.setTransition(a,g,n,a.from),a.to=c.effects.setTransition(a,g,p,a.to));c.effects.save(a,t?d:e);a.show();c.effects.createWrapper(a);a.css("overflow","hidden").css(a.from);if("content"==q||"both"==q)f=f.concat(["marginTop","marginBottom"]).concat(g),
l=l.concat(["marginLeft","marginRight"]),k=d.concat(f).concat(l),a.find("*[width]").each(function(){var a=c(this);t&&c.effects.save(a,k);var d=a.height(),e=a.width();a.from={height:d*n,width:e*r};a.to={height:d*p,width:e*s};n!=p&&(a.from=c.effects.setTransition(a,f,n,a.from),a.to=c.effects.setTransition(a,f,p,a.to));r!=s&&(a.from=c.effects.setTransition(a,l,r,a.from),a.to=c.effects.setTransition(a,l,s,a.to));a.css(a.from);a.animate(a.to,b.duration,b.options.easing,function(){t&&c.effects.restore(a,
k)})});a.animate(a.to,{queue:!1,duration:b.duration,easing:b.options.easing,complete:function(){0===a.to.opacity&&a.css("opacity",a.from.opacity);"hide"==u&&a.hide();c.effects.restore(a,t?d:e);c.effects.removeWrapper(a);b.callback&&b.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
