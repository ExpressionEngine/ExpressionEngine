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
(function(b){function l(){if(b.browser.msie){var a=b(document).height(),d=b(window).height();return[window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,a-d<20?d:a]}return[b(window).width(),b(document).height()]}function i(a){if(a)return a.call(b.mask)}b.tools=b.tools||{version:"1.2.1"};var m;m=b.tools.expose={conf:{maskId:"exposeMask",loadSpeed:"slow",closeSpeed:"fast",closeOnClick:true,closeOnEsc:true,zIndex:9998,opacity:0.8,startOpacity:0,color:"#fff",onLoad:null,
onClose:null}};var c,j,f,g,k;b.mask={load:function(a,d){if(f)return this;if(typeof a=="string")a={color:a};a=a||g;g=a=b.extend(b.extend({},m.conf),a);c=b("#"+a.maskId);if(!c.length){c=b("<div/>").attr("id",a.maskId);b("body").append(c)}var h=l();c.css({position:"absolute",top:0,left:0,width:h[0],height:h[1],display:"none",opacity:a.startOpacity,zIndex:a.zIndex});h=c.css("backgroundColor");if(!h||h=="transparent"||h=="rgba(0, 0, 0, 0)")c.css("backgroundColor",a.color);if(i(a.onBeforeLoad)===false)return this;
a.closeOnEsc&&b(document).bind("keydown.mask",function(e){e.keyCode==27&&b.mask.close(e)});a.closeOnClick&&c.bind("click.mask",function(e){b.mask.close(e)});b(window).bind("resize.mask",function(){b.mask.fit()});if(d&&d.length){k=d.eq(0).css("zIndex");b.each(d,function(){var e=b(this);/relative|absolute|fixed/i.test(e.css("position"))||e.css("position","relative")});j=d.css({zIndex:Math.max(a.zIndex+1,k=="auto"?0:k)})}c.css({display:"block"}).fadeTo(a.loadSpeed,a.opacity,function(){b.mask.fit();i(a.onLoad)});
f=true;return this},close:function(){if(f){if(i(g.onBeforeClose)===false)return this;c.fadeOut(g.closeSpeed,function(){i(g.onClose);j&&j.css({zIndex:k})});b(document).unbind("keydown.mask");c.unbind("click.mask");b(window).unbind("resize.mask");f=false}return this},fit:function(){if(f){var a=l();c.css({width:a[0],height:a[1]})}},getMask:function(){return c},isLoaded:function(){return f},getConf:function(){return g},getExposed:function(){return j}};b.fn.mask=function(a){b.mask.load(a);return this};
b.fn.expose=function(a){b.mask.load(a,this);return this}})(jQuery);
