/**
 * @license 
 * jQuery Tools 1.2.1 / Mask - Dim the lights
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/toolbox/mask.html
 *
 * Since: Mar 2010
 * Date:    Tue May 11 09:22:32 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.1 / Mask - Dim the lights

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/toolbox/mask.html

 Since: Mar 2010
 Date:    Tue May 11 09:22:32 2010 +0000 
*/
(function(b){function m(){if(b.browser.msie){var a=b(document).height(),c=b(window).height();return[window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,20>a-c?c:a]}return[b(window).width(),b(document).height()]}function h(a){if(a)return a.call(b.mask)}b.tools=b.tools||{version:"1.2.1"};var n;n=b.tools.expose={conf:{maskId:"exposeMask",loadSpeed:"slow",closeSpeed:"fast",closeOnClick:!0,closeOnEsc:!0,zIndex:9998,opacity:0.8,startOpacity:0,color:"#fff",onLoad:null,onClose:null}};
var c,k,d,e,l;b.mask={load:function(a,f){if(d)return this;"string"==typeof a&&(a={color:a});a=a||e;e=a=b.extend(b.extend({},n.conf),a);c=b("#"+a.maskId);c.length||(c=b("<div/>").attr("id",a.maskId),b("body").append(c));var g=m();c.css({position:"absolute",top:0,left:0,width:g[0],height:g[1],display:"none",opacity:a.startOpacity,zIndex:a.zIndex});(g=c.css("backgroundColor"))&&"transparent"!=g&&"rgba(0, 0, 0, 0)"!=g||c.css("backgroundColor",a.color);if(!1===h(a.onBeforeLoad))return this;a.closeOnEsc&&
b(document).bind("keydown.mask",function(a){27==a.keyCode&&b.mask.close(a)});a.closeOnClick&&c.bind("click.mask",function(a){b.mask.close(a)});b(window).bind("resize.mask",function(){b.mask.fit()});f&&f.length&&(l=f.eq(0).css("zIndex"),b.each(f,function(){var a=b(this);/relative|absolute|fixed/i.test(a.css("position"))||a.css("position","relative")}),k=f.css({zIndex:Math.max(a.zIndex+1,"auto"==l?0:l)}));c.css({display:"block"}).fadeTo(a.loadSpeed,a.opacity,function(){b.mask.fit();h(a.onLoad)});d=
!0;return this},close:function(){if(d){if(!1===h(e.onBeforeClose))return this;c.fadeOut(e.closeSpeed,function(){h(e.onClose);k&&k.css({zIndex:l})});b(document).unbind("keydown.mask");c.unbind("click.mask");b(window).unbind("resize.mask");d=!1}return this},fit:function(){if(d){var a=m();c.css({width:a[0],height:a[1]})}},getMask:function(){return c},isLoaded:function(){return d},getConf:function(){return e},getExposed:function(){return k}};b.fn.mask=function(a){b.mask.load(a);return this};b.fn.expose=
function(a){b.mask.load(a,this);return this}})(jQuery);
