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

(function(c){c.effects.puff=function(b){return this.queue(function(){var a=c(this),d=c.effects.setMode(a,b.options.mode||"hide"),e=parseInt(b.options.percent,10)||150,i=e/100,g={height:a.height(),width:a.width()};c.extend(b.options,{fade:!0,mode:d,percent:"hide"==d?e:100,from:"hide"==d?g:{height:g.height*i,width:g.width*i}});a.effect("scale",b.options,b.duration,b.callback);a.dequeue()})};c.effects.scale=function(b){return this.queue(function(){var a=c(this),d=c.extend(!0,{},b.options),e=c.effects.setMode(a,
b.options.mode||"effect"),i=parseInt(b.options.percent,10)||(0==parseInt(b.options.percent,10)?0:"hide"==e?0:100),g=b.options.direction||"both",f=b.options.origin;if("effect"!=e)d.origin=f||["middle","center"],d.restore=!0;f={height:a.height(),width:a.width()};a.from=b.options.from||("show"==e?{height:0,width:0}:f);a.to={height:f.height*("horizontal"!=g?i/100:1),width:f.width*("vertical"!=g?i/100:1)};if(b.options.fade){if("show"==e)a.from.opacity=0,a.to.opacity=1;if("hide"==e)a.from.opacity=1,a.to.opacity=
0}d.from=a.from;d.to=a.to;d.mode=e;a.effect("size",d,b.duration,b.callback);a.dequeue()})};c.effects.size=function(b){return this.queue(function(){var a=c(this),d="position,top,bottom,left,right,width,height,overflow,opacity".split(","),e="position,top,bottom,left,right,overflow,opacity".split(","),i=["width","height","overflow"],g=["fontSize"],f=["borderTopWidth","borderBottomWidth","paddingTop","paddingBottom"],j=["borderLeftWidth","borderRightWidth","paddingLeft","paddingRight"],r=c.effects.setMode(a,
b.options.mode||"effect"),q=b.options.restore||!1,n=b.options.scale||"both",k=b.options.origin,h={height:a.height(),width:a.width()};a.from=b.options.from||h;a.to=b.options.to||h;if(k)k=c.effects.getBaseline(k,h),a.from.top=(h.height-a.from.height)*k.y,a.from.left=(h.width-a.from.width)*k.x,a.to.top=(h.height-a.to.height)*k.y,a.to.left=(h.width-a.to.width)*k.x;var l=a.from.height/h.height,o=a.from.width/h.width,m=a.to.height/h.height,p=a.to.width/h.width;if("box"==n||"both"==n){if(l!=m)d=d.concat(f),
a.from=c.effects.setTransition(a,f,l,a.from),a.to=c.effects.setTransition(a,f,m,a.to);if(o!=p)d=d.concat(j),a.from=c.effects.setTransition(a,j,o,a.from),a.to=c.effects.setTransition(a,j,p,a.to)}if(("content"==n||"both"==n)&&l!=m)d=d.concat(g),a.from=c.effects.setTransition(a,g,l,a.from),a.to=c.effects.setTransition(a,g,m,a.to);c.effects.save(a,q?d:e);a.show();c.effects.createWrapper(a);a.css("overflow","hidden").css(a.from);if("content"==n||"both"==n)f=f.concat(["marginTop","marginBottom"]).concat(g),
j=j.concat(["marginLeft","marginRight"]),i=d.concat(f).concat(j),a.find("*[width]").each(function(){var a=c(this);q&&c.effects.save(a,i);var d=a.height(),e=a.width();a.from={height:d*l,width:e*o};a.to={height:d*m,width:e*p};if(l!=m)a.from=c.effects.setTransition(a,f,l,a.from),a.to=c.effects.setTransition(a,f,m,a.to);if(o!=p)a.from=c.effects.setTransition(a,j,o,a.from),a.to=c.effects.setTransition(a,j,p,a.to);a.css(a.from);a.animate(a.to,b.duration,b.options.easing,function(){q&&c.effects.restore(a,
i)})});a.animate(a.to,{queue:!1,duration:b.duration,easing:b.options.easing,complete:function(){0===a.to.opacity&&a.css("opacity",a.from.opacity);"hide"==r&&a.hide();c.effects.restore(a,q?d:e);c.effects.removeWrapper(a);b.callback&&b.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
