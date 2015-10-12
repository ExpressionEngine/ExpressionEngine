/*!
 * jQuery UI Effects Blind @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/blind-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.blind=function(t,s){
// Create element
var i,o,f,n=e(this),c=/up|down|vertical/,r=/up|left|vertical|horizontal/,a=["position","top","bottom","left","right","height","width"],p=e.effects.setMode(n,t.mode||"hide"),d=t.direction||"up",u=c.test(d),h=u?"height":"width",l=u?"top":"left",m=r.test(d),v={},w="show"===p;
// if already wrapped, the wrapper's properties are my property. #6245
n.parent().is(".ui-effects-wrapper")?e.effects.save(n.parent(),a):e.effects.save(n,a),n.show(),i=e.effects.createWrapper(n).css({overflow:"hidden"}),o=i[h](),f=parseFloat(i.css(l))||0,v[h]=w?o:0,m||(n.css(u?"bottom":"right",0).css(u?"top":"left","auto").css({position:"absolute"}),v[l]=w?f:o+f),
// start at 0 if we are showing
w&&(i.css(h,0),m||i.css(l,f+o)),
// Animate
i.animate(v,{duration:t.duration,easing:t.easing,queue:!1,complete:function(){"hide"===p&&n.hide(),e.effects.restore(n,a),e.effects.removeWrapper(n),s()}})}});