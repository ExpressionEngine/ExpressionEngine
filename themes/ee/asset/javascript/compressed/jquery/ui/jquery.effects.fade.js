/*!
 * jQuery UI Effects Fade @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/fade-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.fade=function(t,f){var n=e(this),i=e.effects.setMode(n,t.mode||"toggle");n.animate({opacity:i},{queue:!1,duration:t.duration,easing:t.easing,complete:f})}});