/*!
 * jQuery UI Effects Size @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/size-effect/
 */
!function(t){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],t):
// Browser globals
t(jQuery)}(function(t){return t.effects.effect.size=function(o,e){
// Create element
var i,r,f,h=t(this),n=["position","top","bottom","left","right","width","height","overflow","opacity"],
// Always restore
s=["position","top","bottom","left","right","overflow","opacity"],
// Copy for children
c=["width","height","overflow"],d=["fontSize"],a=["borderTopWidth","borderBottomWidth","paddingTop","paddingBottom"],m=["borderLeftWidth","borderRightWidth","paddingLeft","paddingRight"],
// Set options
g=t.effects.setMode(h,o.mode||"effect"),u=o.restore||"effect"!==g,p=o.scale||"both",y=o.origin||["middle","center"],w=h.css("position"),x=u?n:s,l={height:0,width:0,outerHeight:0,outerWidth:0};"show"===g&&h.show(),i={height:h.height(),width:h.width(),outerHeight:h.outerHeight(),outerWidth:h.outerWidth()},"toggle"===o.mode&&"show"===g?(h.from=o.to||l,h.to=o.from||i):(h.from=o.from||("show"===g?l:i),h.to=o.to||("hide"===g?l:i)),
// Set scaling factor
f={from:{y:h.from.height/i.height,x:h.from.width/i.width},to:{y:h.to.height/i.height,x:h.to.width/i.width}},
// Scale the css box
("box"===p||"both"===p)&&(
// Vertical props scaling
f.from.y!==f.to.y&&(x=x.concat(a),h.from=t.effects.setTransition(h,a,f.from.y,h.from),h.to=t.effects.setTransition(h,a,f.to.y,h.to)),
// Horizontal props scaling
f.from.x!==f.to.x&&(x=x.concat(m),h.from=t.effects.setTransition(h,m,f.from.x,h.from),h.to=t.effects.setTransition(h,m,f.to.x,h.to))),
// Scale the content
("content"===p||"both"===p)&&f.from.y!==f.to.y&&(x=x.concat(d).concat(c),h.from=t.effects.setTransition(h,d,f.from.y,h.from),h.to=t.effects.setTransition(h,d,f.to.y,h.to)),t.effects.save(h,x),h.show(),t.effects.createWrapper(h),h.css("overflow","hidden").css(h.from),
// Adjust
y&&(// Calculate baseline shifts
r=t.effects.getBaseline(y,i),h.from.top=(i.outerHeight-h.outerHeight())*r.y,h.from.left=(i.outerWidth-h.outerWidth())*r.x,h.to.top=(i.outerHeight-h.to.outerHeight)*r.y,h.to.left=(i.outerWidth-h.to.outerWidth)*r.x),h.css(h.from),// set top & left
// Animate
("content"===p||"both"===p)&&(// Scale the children
// Add margins/font-size
a=a.concat(["marginTop","marginBottom"]).concat(d),m=m.concat(["marginLeft","marginRight"]),c=n.concat(a).concat(m),h.find("*[width]").each(function(){var e=t(this),i={height:e.height(),width:e.width(),outerHeight:e.outerHeight(),outerWidth:e.outerWidth()};u&&t.effects.save(e,c),e.from={height:i.height*f.from.y,width:i.width*f.from.x,outerHeight:i.outerHeight*f.from.y,outerWidth:i.outerWidth*f.from.x},e.to={height:i.height*f.to.y,width:i.width*f.to.x,outerHeight:i.height*f.to.y,outerWidth:i.width*f.to.x},
// Vertical props scaling
f.from.y!==f.to.y&&(e.from=t.effects.setTransition(e,a,f.from.y,e.from),e.to=t.effects.setTransition(e,a,f.to.y,e.to)),
// Horizontal props scaling
f.from.x!==f.to.x&&(e.from=t.effects.setTransition(e,m,f.from.x,e.from),e.to=t.effects.setTransition(e,m,f.to.x,e.to)),
// Animate children
e.css(e.from),e.animate(e.to,o.duration,o.easing,function(){
// Restore children
u&&t.effects.restore(e,c)})})),
// Animate
h.animate(h.to,{queue:!1,duration:o.duration,easing:o.easing,complete:function(){0===h.to.opacity&&h.css("opacity",h.from.opacity),"hide"===g&&h.hide(),t.effects.restore(h,x),u||(
// we need to calculate our new positioning based on the scaling
"static"===w?h.css({position:"relative",top:h.to.top,left:h.to.left}):t.each(["top","left"],function(t,o){h.css(o,function(o,e){var i=parseInt(e,10),r=t?h.to.left:h.to.top;
// if original was "auto", recalculate the new value from wrapper
// if original was "auto", recalculate the new value from wrapper
return"auto"===e?r+"px":i+r+"px"})})),t.effects.removeWrapper(h),e()}})}});