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
(function(b){function m(a,c){var d=this,h=a.add(d),n=b(window),e,f,k,g=b.tools.expose&&(c.mask||c.expose),l=Math.random().toString().slice(10);if(g)typeof g=="string"&&(g={color:g}),g.closeOnClick=g.closeOnEsc=!1;var i=c.target||a.attr("rel");f=i?b(i):a;if(!f.length)throw"Could not find Overlay: "+i;a&&a.index(f)==-1&&a.click(function(b){d.load(b);return b.preventDefault()});b.extend(d,{load:function(a){if(d.isOpened())return d;var p=o[c.effect];if(!p)throw'Overlay: cannot find effect : "'+c.effect+
'"';c.oneInstance&&b.each(q,function(){this.close(a)});a=a||b.Event();a.type="onBeforeLoad";h.trigger(a);if(a.isDefaultPrevented())return d;k=!0;g&&b(f).expose(g);var j=c.top,e=c.left,i=f.outerWidth({margin:!0}),m=f.outerHeight({margin:!0});typeof j=="string"&&(j=j=="center"?Math.max((n.height()-m)/2,0):parseInt(j,10)/100*n.height());e=="center"&&(e=Math.max((n.width()-i)/2,0));p[0].call(d,{top:j,left:e},function(){if(k)a.type="onLoad",h.trigger(a)});if(g&&c.closeOnClick)b.mask.getMask().one("click",
d.close);c.closeOnClick&&b(document).bind("click."+l,function(a){b(a.target).parents(f).length||d.close(a)});c.closeOnEsc&&b(document).bind("keydown."+l,function(a){a.keyCode==27&&d.close(a)});return d},close:function(a){if(!d.isOpened())return d;a=a||b.Event();a.type="onBeforeClose";h.trigger(a);if(!a.isDefaultPrevented())return k=!1,o[c.effect][1].call(d,function(){a.type="onClose";h.trigger(a)}),b(document).unbind("click."+l).unbind("keydown."+l),g&&b.mask.close(),d},getOverlay:function(){return f},
getTrigger:function(){return a},getClosers:function(){return e},isOpened:function(){return k},getConf:function(){return c}});b.each("onBeforeLoad,onStart,onLoad,onBeforeClose,onClose".split(","),function(a,e){b.isFunction(c[e])&&b(d).bind(e,c[e]);d[e]=function(a){b(d).bind(e,a);return d}});e=f.find(c.close||".close");!e.length&&!c.close&&(e=b('<a class="close"></a>'),f.prepend(e));e.click(function(a){d.close(a)});c.load&&d.load()}b.tools=b.tools||{version:"1.2.3"};b.tools.overlay={addEffect:function(a,
b,d){o[a]=[b,d]},conf:{close:null,closeOnClick:!0,closeOnEsc:!0,closeSpeed:"fast",effect:"default",fixed:!b.browser.msie||b.browser.version>6,left:"center",load:!1,mask:null,oneInstance:!0,speed:"normal",target:null,top:"10%"}};var q=[],o={};b.tools.overlay.addEffect("default",function(a,c){var d=this.getConf(),h=b(window);d.fixed||(a.top+=h.scrollTop(),a.left+=h.scrollLeft());a.position=d.fixed?"fixed":"absolute";this.getOverlay().css(a).fadeIn(d.speed,c)},function(a){this.getOverlay().fadeOut(this.getConf().closeSpeed,
a)});b.fn.overlay=function(a){var c=this.data("overlay");if(c)return c;b.isFunction(a)&&(a={onBeforeLoad:a});a=b.extend(!0,{},b.tools.overlay.conf,a);this.each(function(){c=new m(b(this),a);q.push(c);b(this).data("overlay",c)});return a.api?c:this}})(jQuery);
