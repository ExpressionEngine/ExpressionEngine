/**
 * @license 
 * jQuery Tools 1.2.3 Overlay - Overlay base. Extend it.
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/overlay/
 *
 * Since: March 2008
 * Date:    Mon Jun 7 13:43:53 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.3 Overlay - Overlay base. Extend it.

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/overlay/

 Since: March 2008
 Date:    Mon Jun 7 13:43:53 2010 +0000 
*/
(function(b){function p(a,c){var d=this,h=a.add(d),q=b(window),e,f,m,g=b.tools.expose&&(c.mask||c.expose),n=Math.random().toString().slice(10);g&&("string"==typeof g&&(g={color:g}),g.closeOnClick=g.closeOnEsc=!1);var k=c.target||a.attr("rel");f=k?b(k):a;if(!f.length)throw"Could not find Overlay: "+k;a&&-1==a.index(f)&&a.click(function(b){d.load(b);return b.preventDefault()});b.extend(d,{load:function(a){if(d.isOpened())return d;var s=r[c.effect];if(!s)throw'Overlay: cannot find effect : "'+c.effect+
'"';c.oneInstance&&b.each(t,function(){this.close(a)});a=a||b.Event();a.type="onBeforeLoad";h.trigger(a);if(a.isDefaultPrevented())return d;m=!0;g&&b(f).expose(g);var l=c.top,e=c.left,k=f.outerWidth({margin:!0}),p=f.outerHeight({margin:!0});"string"==typeof l&&(l="center"==l?Math.max((q.height()-p)/2,0):parseInt(l,10)/100*q.height());"center"==e&&(e=Math.max((q.width()-k)/2,0));s[0].call(d,{top:l,left:e},function(){m&&(a.type="onLoad",h.trigger(a))});if(g&&c.closeOnClick)b.mask.getMask().one("click",
d.close);c.closeOnClick&&b(document).bind("click."+n,function(a){b(a.target).parents(f).length||d.close(a)});c.closeOnEsc&&b(document).bind("keydown."+n,function(a){27==a.keyCode&&d.close(a)});return d},close:function(a){if(!d.isOpened())return d;a=a||b.Event();a.type="onBeforeClose";h.trigger(a);if(!a.isDefaultPrevented())return m=!1,r[c.effect][1].call(d,function(){a.type="onClose";h.trigger(a)}),b(document).unbind("click."+n).unbind("keydown."+n),g&&b.mask.close(),d},getOverlay:function(){return f},
getTrigger:function(){return a},getClosers:function(){return e},isOpened:function(){return m},getConf:function(){return c}});b.each(["onBeforeLoad","onStart","onLoad","onBeforeClose","onClose"],function(a,e){b.isFunction(c[e])&&b(d).bind(e,c[e]);d[e]=function(a){b(d).bind(e,a);return d}});e=f.find(c.close||".close");e.length||c.close||(e=b('<a class="close"></a>'),f.prepend(e));e.click(function(a){d.close(a)});c.load&&d.load()}b.tools=b.tools||{version:"1.2.3"};b.tools.overlay={addEffect:function(a,
b,d){r[a]=[b,d]},conf:{close:null,closeOnClick:!0,closeOnEsc:!0,closeSpeed:"fast",effect:"default",fixed:!b.browser.msie||6<b.browser.version,left:"center",load:!1,mask:null,oneInstance:!0,speed:"normal",target:null,top:"10%"}};var t=[],r={};b.tools.overlay.addEffect("default",function(a,c){var d=this.getConf(),h=b(window);d.fixed||(a.top+=h.scrollTop(),a.left+=h.scrollLeft());a.position=d.fixed?"fixed":"absolute";this.getOverlay().css(a).fadeIn(d.speed,c)},function(a){this.getOverlay().fadeOut(this.getConf().closeSpeed,
a)});b.fn.overlay=function(a){var c=this.data("overlay");if(c)return c;b.isFunction(a)&&(a={onBeforeLoad:a});a=b.extend(!0,{},b.tools.overlay.conf,a);this.each(function(){c=new p(b(this),a);t.push(c);b(this).data("overlay",c)});return a.api?c:this}})(jQuery);
