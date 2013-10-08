/*!
 * jQuery UI Effects Explode @VERSION
 *
 * Copyright 2012, AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * http://docs.jquery.com/UI/Effects/Explode
 *
 * Depends:
 *	jquery.effects.core.js
 */

(function(l,m){l.effects.explode=function(a){return this.queue(function(){var c=a.options.pieces?Math.round(Math.sqrt(a.options.pieces)):3,d=a.options.pieces?Math.round(Math.sqrt(a.options.pieces)):3;a.options.mode="toggle"==a.options.mode?l(this).is(":visible")?"hide":"show":a.options.mode;var b=l(this).show().css("visibility","hidden"),e=b.offset();e.top-=parseInt(b.css("marginTop"),10)||0;e.left-=parseInt(b.css("marginLeft"),10)||0;for(var h=b.outerWidth(!0),k=b.outerHeight(!0),f=0;f<c;f++)for(var g=
0;g<d;g++)b.clone().appendTo("body").wrap("<div></div>").css({position:"absolute",visibility:"visible",left:-g*(h/d),top:-f*(k/c)}).parent().addClass("ui-effects-explode").css({position:"absolute",overflow:"hidden",width:h/d,height:k/c,left:e.left+g*(h/d)+("show"==a.options.mode?(g-Math.floor(d/2))*(h/d):0),top:e.top+f*(k/c)+("show"==a.options.mode?(f-Math.floor(c/2))*(k/c):0),opacity:"show"==a.options.mode?0:1}).animate({left:e.left+g*(h/d)+("show"==a.options.mode?0:(g-Math.floor(d/2))*(h/d)),top:e.top+
f*(k/c)+("show"==a.options.mode?0:(f-Math.floor(c/2))*(k/c)),opacity:"show"==a.options.mode?1:0},a.duration||500);setTimeout(function(){"show"==a.options.mode?b.css({visibility:"visible"}):b.css({visibility:"visible"}).hide();a.callback&&a.callback.apply(b[0]);b.dequeue();l("div.ui-effects-explode").remove()},a.duration||500)})}})(jQuery);
