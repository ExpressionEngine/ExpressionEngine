/**
 * @license 
 * jQuery Tools 1.2.0 Mousewheel
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/toolbox/mousewheel.html
 * 
 * based on jquery.event.wheel.js ~ rev 1 ~ 
 * Copyright (c) 2008, Three Dub Media
 * http://threedubmedia.com 
 *
 * Since: Mar 2010
 * Date:    Tue Apr 20 19:26:58 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.0 Mousewheel

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/toolbox/mousewheel.html

 based on jquery.event.wheel.js ~ rev 1 ~ 
 Copyright (c) 2008, Three Dub Media
 http://threedubmedia.com 

 Since: Mar 2010
 Date:    Tue Apr 20 19:26:58 2010 +0000 
*/
(function(b){function c(a){switch(a.type){case "mousemove":return b.extend(a.data,{clientX:a.clientX,clientY:a.clientY,pageX:a.pageX,pageY:a.pageY});case "DOMMouseScroll":b.extend(a,a.data);a.delta=-a.detail/3;break;case "mousewheel":a.delta=a.wheelDelta/120;break}a.type="wheel";return b.event.handle.call(this,a,a.delta)}b.fn.mousewheel=function(a){return this[a?"bind":"trigger"]("wheel",a)};b.event.special.wheel={setup:function(){b.event.add(this,d,c,{})},teardown:function(){b.event.remove(this,
d,c)}};var d=!b.browser.mozilla?"mousewheel":"DOMMouseScroll"+(b.browser.version<"1.9"?" mousemove":"")})(jQuery);
