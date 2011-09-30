/*
 * jQuery UI Effects Scale 1.8.16
 *
 * Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Scale
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(c){c.effects.puff=function(b){return this.queue(function(){var a=c(this),d=c.effects.setMode(a,b.options.mode||"hide"),f=parseInt(b.options.percent,10)||150,i=f/100,g={height:a.height(),width:a.width()};c.extend(b.options,{fade:!0,mode:d,percent:d=="hide"?f:100,from:d=="hide"?g:{height:g.height*i,width:g.width*i}});a.effect("scale",b.options,b.duration,b.callback);a.dequeue()})};c.effects.scale=function(b){return this.queue(function(){var a=c(this),d=c.extend(!0,{},b.options),f=c.effects.setMode(a,
b.options.mode||"effect"),i=parseInt(b.options.percent,10)||(parseInt(b.options.percent,10)==0?0:f=="hide"?0:100),g=b.options.direction||"both",e=b.options.origin;if(f!="effect")d.origin=e||["middle","center"],d.restore=!0;e={height:a.height(),width:a.width()};a.from=b.options.from||(f=="show"?{height:0,width:0}:e);a.to={height:e.height*(g!="horizontal"?i/100:1),width:e.width*(g!="vertical"?i/100:1)};if(b.options.fade){if(f=="show")a.from.opacity=0,a.to.opacity=1;if(f=="hide")a.from.opacity=1,a.to.opacity=
0}d.from=a.from;d.to=a.to;d.mode=f;a.effect("size",d,b.duration,b.callback);a.dequeue()})};c.effects.size=function(b){return this.queue(function(){var a=c(this),d="position,top,bottom,left,right,width,height,overflow,opacity".split(","),f="position,top,bottom,left,right,overflow,opacity".split(","),i=["width","height","overflow"],g=["fontSize"],e=["borderTopWidth","borderBottomWidth","paddingTop","paddingBottom"],j=["borderLeftWidth","borderRightWidth","paddingLeft","paddingRight"],r=c.effects.setMode(a,
b.options.mode||"effect"),q=b.options.restore||!1,n=b.options.scale||"both",k=b.options.origin,h={height:a.height(),width:a.width()};a.from=b.options.from||h;a.to=b.options.to||h;if(k)k=c.effects.getBaseline(k,h),a.from.top=(h.height-a.from.height)*k.y,a.from.left=(h.width-a.from.width)*k.x,a.to.top=(h.height-a.to.height)*k.y,a.to.left=(h.width-a.to.width)*k.x;var l=a.from.height/h.height,o=a.from.width/h.width,m=a.to.height/h.height,p=a.to.width/h.width;if(n=="box"||n=="both"){if(l!=m)d=d.concat(e),
a.from=c.effects.setTransition(a,e,l,a.from),a.to=c.effects.setTransition(a,e,m,a.to);if(o!=p)d=d.concat(j),a.from=c.effects.setTransition(a,j,o,a.from),a.to=c.effects.setTransition(a,j,p,a.to)}if((n=="content"||n=="both")&&l!=m)d=d.concat(g),a.from=c.effects.setTransition(a,g,l,a.from),a.to=c.effects.setTransition(a,g,m,a.to);c.effects.save(a,q?d:f);a.show();c.effects.createWrapper(a);a.css("overflow","hidden").css(a.from);if(n=="content"||n=="both")e=e.concat(["marginTop","marginBottom"]).concat(g),
j=j.concat(["marginLeft","marginRight"]),i=d.concat(e).concat(j),a.find("*[width]").each(function(){child=c(this);q&&c.effects.save(child,i);var a=child.height(),d=child.width();child.from={height:a*l,width:d*o};child.to={height:a*m,width:d*p};if(l!=m)child.from=c.effects.setTransition(child,e,l,child.from),child.to=c.effects.setTransition(child,e,m,child.to);if(o!=p)child.from=c.effects.setTransition(child,j,o,child.from),child.to=c.effects.setTransition(child,j,p,child.to);child.css(child.from);
child.animate(child.to,b.duration,b.options.easing,function(){q&&c.effects.restore(child,i)})});a.animate(a.to,{queue:!1,duration:b.duration,easing:b.options.easing,complete:function(){a.to.opacity===0&&a.css("opacity",a.from.opacity);r=="hide"&&a.hide();c.effects.restore(a,q?d:f);c.effects.removeWrapper(a);b.callback&&b.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
