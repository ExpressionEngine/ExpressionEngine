/*!
 * jQuery UI Effects Fold @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/fold-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.fold=function(t,i){
// Create element
var h,f,n=e(this),o=["position","top","bottom","left","right","height","width"],s=e.effects.setMode(n,t.mode||"hide"),d="show"===s,r="hide"===s,c=t.size||15,a=/([0-9]+)%/.exec(c),g=!!t.horizFirst,w=d!==g,u=w?["width","height"]:["height","width"],p=t.duration/2,m={},v={};e.effects.save(n,o),n.show(),
// Create Wrapper
h=e.effects.createWrapper(n).css({overflow:"hidden"}),f=w?[h.width(),h.height()]:[h.height(),h.width()],a&&(c=parseInt(a[1],10)/100*f[r?0:1]),d&&h.css(g?{height:0,width:c}:{height:c,width:0}),
// Animation
m[u[0]]=d?f[0]:c,v[u[1]]=d?f[1]:0,
// Animate
h.animate(m,p,t.easing).animate(v,p,t.easing,function(){r&&n.hide(),e.effects.restore(n,o),e.effects.removeWrapper(n),i()})}});