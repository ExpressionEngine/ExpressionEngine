/*!
 * jQuery UI Effects Transfer @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/transfer-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.transfer=function(t,i){var n=e(this),f=e(t.to),o="fixed"===f.css("position"),s=e("body"),d=o?s.scrollTop():0,r=o?s.scrollLeft():0,c=f.offset(),a={top:c.top-d,left:c.left-r,height:f.innerHeight(),width:f.innerWidth()},l=n.offset(),u=e("<div class='ui-effects-transfer'></div>").appendTo(document.body).addClass(t.className).css({top:l.top-d,left:l.left-r,height:n.innerHeight(),width:n.innerWidth(),position:o?"fixed":"absolute"}).animate(a,t.duration,t.easing,function(){u.remove(),i()})}});