/*!
 * jQuery UI Effects Bounce @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/bounce-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.bounce=function(t,i){var o,f,c,n=e(this),a=["position","top","bottom","left","right","height","width"],
// defaults:
s=e.effects.setMode(n,t.mode||"effect"),p="hide"===s,u="show"===s,r=t.direction||"up",d=t.distance,h=t.times||5,
// number of internal animations
m=2*h+(u||p?1:0),y=t.duration/m,l=t.easing,
// utility:
g="up"===r||"down"===r?"top":"left",w="up"===r||"left"===r,
// we will need to re-assemble the queue to stack our animations in place
q=n.queue(),v=q.length;
// Bounces up/down/left/right then back to 0 -- times * 2 animations happen here
for(
// Avoid touching opacity to prevent clearType and PNG issues in IE
(u||p)&&a.push("opacity"),e.effects.save(n,a),n.show(),e.effects.createWrapper(n),// Create Wrapper
// default distance for the BIGGEST bounce is the outer Distance / 3
d||(d=n["top"===g?"outerHeight":"outerWidth"]()/3),u&&(c={opacity:1},c[g]=0,
// if we are showing, force opacity 0 and set the initial position
// then do the "first" animation
n.css("opacity",0).css(g,w?2*-d:2*d).animate(c,y,l)),
// start at the smallest distance if we are hiding
p&&(d/=Math.pow(2,h-1)),c={},c[g]=0,o=0;h>o;o++)f={},f[g]=(w?"-=":"+=")+d,n.animate(f,y,l).animate(c,y,l),d=p?2*d:d/2;
// Last Bounce when Hiding
p&&(f={opacity:0},f[g]=(w?"-=":"+=")+d,n.animate(f,y,l)),n.queue(function(){p&&n.hide(),e.effects.restore(n,a),e.effects.removeWrapper(n),i()}),
// inject all the animations we just queued to be first in line (after "inprogress")
v>1&&q.splice.apply(q,[1,0].concat(q.splice(v,m+1))),n.dequeue()}});