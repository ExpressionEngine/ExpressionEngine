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
!function(o){o.effects.explode=function(i){return this.queue(function(){var t=i.options.pieces?Math.round(Math.sqrt(i.options.pieces)):3,e=i.options.pieces?Math.round(Math.sqrt(i.options.pieces)):3;i.options.mode="toggle"==i.options.mode?o(this).is(":visible")?"hide":"show":i.options.mode;var s=o(this).show().css("visibility","hidden"),p=s.offset();p.top-=parseInt(s.css("marginTop"),10)||0,p.left-=parseInt(s.css("marginLeft"),10)||0;for(var n=s.outerWidth(!0),a=s.outerHeight(!0),d=0;t>d;d++)for(var l=0;e>l;l++)s.clone().appendTo("body").wrap("<div></div>").css({position:"absolute",visibility:"visible",left:-l*(n/e),top:-d*(a/t)}).parent().addClass("ui-effects-explode").css({position:"absolute",overflow:"hidden",width:n/e,height:a/t,left:p.left+l*(n/e)+("show"==i.options.mode?(l-Math.floor(e/2))*(n/e):0),top:p.top+d*(a/t)+("show"==i.options.mode?(d-Math.floor(t/2))*(a/t):0),opacity:"show"==i.options.mode?0:1}).animate({left:p.left+l*(n/e)+("show"==i.options.mode?0:(l-Math.floor(e/2))*(n/e)),top:p.top+d*(a/t)+("show"==i.options.mode?0:(d-Math.floor(t/2))*(a/t)),opacity:"show"==i.options.mode?1:0},i.duration||500);setTimeout(function(){"show"==i.options.mode?s.css({visibility:"visible"}):s.css({visibility:"visible"}).hide(),i.callback&&i.callback.apply(s[0]),s.dequeue(),o("div.ui-effects-explode").remove()},i.duration||500)})}}(jQuery);