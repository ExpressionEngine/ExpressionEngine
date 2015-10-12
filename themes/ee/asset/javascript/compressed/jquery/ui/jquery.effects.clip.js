/*!
 * jQuery UI Effects Clip @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/clip-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.clip=function(t,i){
// Create element
var f,o,c,n=e(this),s=["position","top","bottom","left","right","height","width"],r=e.effects.setMode(n,t.mode||"hide"),a="show"===r,d=t.direction||"vertical",h="vertical"===d,u=h?"height":"width",p=h?"top":"left",l={};
// Save & Show
e.effects.save(n,s),n.show(),
// Create Wrapper
f=e.effects.createWrapper(n).css({overflow:"hidden"}),o="IMG"===n[0].tagName?f:n,c=o[u](),
// Shift
a&&(o.css(u,0),o.css(p,c/2)),
// Create Animation Object:
l[u]=a?c:0,l[p]=a?0:c/2,
// Animate
o.animate(l,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){a||n.hide(),e.effects.restore(n,s),e.effects.removeWrapper(n),i()}})}});