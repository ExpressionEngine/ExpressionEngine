/*!
 * jQuery UI Effects Highlight @VERSION
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/highlight-effect/
 */
!function(e){"function"==typeof define&&define.amd?
// AMD. Register as an anonymous module.
define(["jquery","./effect"],e):
// Browser globals
e(jQuery)}(function(e){return e.effects.effect.highlight=function(o,n){var f=e(this),c=["backgroundImage","backgroundColor","opacity"],t=e.effects.setMode(f,o.mode||"show"),i={backgroundColor:f.css("backgroundColor")};"hide"===t&&(i.opacity=0),e.effects.save(f,c),f.show().css({backgroundImage:"none",backgroundColor:o.color||"#ffff99"}).animate(i,{queue:!1,duration:o.duration,easing:o.easing,complete:function(){"hide"===t&&f.hide(),e.effects.restore(f,c),n()}})}});