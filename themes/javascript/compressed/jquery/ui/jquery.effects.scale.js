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

(function(c){c.effects.puff=function(b){return this.queue(function(){var a=c(this),d=c.effects.setMode(a,b.options.mode||"hide"),g=parseInt(b.options.percent,10)||150,h=g/100,i={height:a.height(),width:a.width()};c.extend(b.options,{fade:!0,mode:d,percent:d=="hide"?g:100,from:d=="hide"?i:{height:i.height*h,width:i.width*h}});a.effect("scale",b.options,b.duration,b.callback);a.dequeue()})};c.effects.scale=function(b){return this.queue(function(){var a=c(this),d=c.extend(!0,{},b.options),g=c.effects.setMode(a,
b.options.mode||"effect"),h=parseInt(b.options.percent,10)||(parseInt(b.options.percent,10)==0?0:g=="hide"?0:100),i=b.options.direction||"both",f=b.options.origin;if(g!="effect")d.origin=f||["middle","center"],d.restore=!0;f={height:a.height(),width:a.width()};a.from=b.options.from||(g=="show"?{height:0,width:0}:f);h={y:i!="horizontal"?h/100:1,x:i!="vertical"?h/100:1};a.to={height:f.height*h.y,width:f.width*h.x};if(b.options.fade){if(g=="show")a.from.opacity=0,a.to.opacity=1;if(g=="hide")a.from.opacity=
1,a.to.opacity=0}d.from=a.from;d.to=a.to;d.mode=g;a.effect("size",d,b.duration,b.callback);a.dequeue()})};c.effects.size=function(b){return this.queue(function(){var a=c(this),d=["position","top","bottom","left","right","width","height","overflow","opacity"],g=["position","top","bottom","left","right","overflow","opacity"],h=["width","height","overflow"],i=["fontSize"],f=["borderTopWidth","borderBottomWidth","paddingTop","paddingBottom"],k=["borderLeftWidth","borderRightWidth","paddingLeft","paddingRight"],
o=c.effects.setMode(a,b.options.mode||"effect"),n=b.options.restore||!1,m=b.options.scale||"both",l=b.options.origin,j={height:a.height(),width:a.width()};a.from=b.options.from||j;a.to=b.options.to||j;if(l)l=c.effects.getBaseline(l,j),a.from.top=(j.height-a.from.height)*l.y,a.from.left=(j.width-a.from.width)*l.x,a.to.top=(j.height-a.to.height)*l.y,a.to.left=(j.width-a.to.width)*l.x;var e={from:{y:a.from.height/j.height,x:a.from.width/j.width},to:{y:a.to.height/j.height,x:a.to.width/j.width}};if(m==
"box"||m=="both"){if(e.from.y!=e.to.y)d=d.concat(f),a.from=c.effects.setTransition(a,f,e.from.y,a.from),a.to=c.effects.setTransition(a,f,e.to.y,a.to);if(e.from.x!=e.to.x)d=d.concat(k),a.from=c.effects.setTransition(a,k,e.from.x,a.from),a.to=c.effects.setTransition(a,k,e.to.x,a.to)}if((m=="content"||m=="both")&&e.from.y!=e.to.y)d=d.concat(i),a.from=c.effects.setTransition(a,i,e.from.y,a.from),a.to=c.effects.setTransition(a,i,e.to.y,a.to);c.effects.save(a,n?d:g);a.show();c.effects.createWrapper(a);
a.css("overflow","hidden").css(a.from);if(m=="content"||m=="both")f=f.concat(["marginTop","marginBottom"]).concat(i),k=k.concat(["marginLeft","marginRight"]),h=d.concat(f).concat(k),a.find("*[width]").each(function(){var a=c(this);n&&c.effects.save(a,h);var d={height:a.height(),width:a.width()};a.from={height:d.height*e.from.y,width:d.width*e.from.x};a.to={height:d.height*e.to.y,width:d.width*e.to.x};if(e.from.y!=e.to.y)a.from=c.effects.setTransition(a,f,e.from.y,a.from),a.to=c.effects.setTransition(a,
f,e.to.y,a.to);if(e.from.x!=e.to.x)a.from=c.effects.setTransition(a,k,e.from.x,a.from),a.to=c.effects.setTransition(a,k,e.to.x,a.to);a.css(a.from);a.animate(a.to,b.duration,b.options.easing,function(){n&&c.effects.restore(a,h)})});a.animate(a.to,{queue:!1,duration:b.duration,easing:b.options.easing,complete:function(){a.to.opacity===0&&a.css("opacity",a.from.opacity);o=="hide"&&a.hide();c.effects.restore(a,n?d:g);c.effects.removeWrapper(a);b.callback&&b.callback.apply(this,arguments);a.dequeue()}})})}})(jQuery);
