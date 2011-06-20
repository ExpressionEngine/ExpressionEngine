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
(function(b){function k(){if(b.browser.msie){var a=b(document).height(),c=b(window).height();return[window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,a-c<20?c:a]}return[b(window).width(),b(document).height()]}function h(a){if(a)return a.call(b.mask)}b.tools=b.tools||{version:"1.2.1"};var l;l=b.tools.expose={conf:{maskId:"exposeMask",loadSpeed:"slow",closeSpeed:"fast",closeOnClick:!0,closeOnEsc:!0,zIndex:9998,opacity:0.8,startOpacity:0,color:"#fff",onLoad:null,onClose:null}};
var c,i,d,e,j;b.mask={load:function(a,g){if(d)return this;typeof a=="string"&&(a={color:a});a=a||e;e=a=b.extend(b.extend({},l.conf),a);c=b("#"+a.maskId);c.length||(c=b("<div/>").attr("id",a.maskId),b("body").append(c));var f=k();c.css({position:"absolute",top:0,left:0,width:f[0],height:f[1],display:"none",opacity:a.startOpacity,zIndex:a.zIndex});f=c.css("backgroundColor");(!f||f=="transparent"||f=="rgba(0, 0, 0, 0)")&&c.css("backgroundColor",a.color);if(h(a.onBeforeLoad)===!1)return this;a.closeOnEsc&&
b(document).bind("keydown.mask",function(a){a.keyCode==27&&b.mask.close(a)});a.closeOnClick&&c.bind("click.mask",function(a){b.mask.close(a)});b(window).bind("resize.mask",function(){b.mask.fit()});g&&g.length&&(j=g.eq(0).css("zIndex"),b.each(g,function(){var a=b(this);/relative|absolute|fixed/i.test(a.css("position"))||a.css("position","relative")}),i=g.css({zIndex:Math.max(a.zIndex+1,j=="auto"?0:j)}));c.css({display:"block"}).fadeTo(a.loadSpeed,a.opacity,function(){b.mask.fit();h(a.onLoad)});d=
!0;return this},close:function(){if(d){if(h(e.onBeforeClose)===!1)return this;c.fadeOut(e.closeSpeed,function(){h(e.onClose);i&&i.css({zIndex:j})});b(document).unbind("keydown.mask");c.unbind("click.mask");b(window).unbind("resize.mask");d=!1}return this},fit:function(){if(d){var a=k();c.css({width:a[0],height:a[1]})}},getMask:function(){return c},isLoaded:function(){return d},getConf:function(){return e},getExposed:function(){return i}};b.fn.mask=function(a){b.mask.load(a);return this};b.fn.expose=
function(a){b.mask.load(a,this);return this}})(jQuery);
