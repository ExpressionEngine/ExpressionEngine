/**
 * @license 
 * jQuery Tools 1.2.0 Tooltip - UI essentials
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/tooltip/
 *
 * Since: November 2008
 * Date:    Tue Apr 20 19:26:58 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.0 Tooltip - UI essentials

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/tooltip/

 Since: November 2008
 Date:    Tue Apr 20 19:26:58 2010 +0000 
*/
(function(e){function p(a,b,c){var e=c.relative?a.position().top:a.offset().top,d=c.relative?a.position().left:a.offset().left,f=c.position[0];e-=b.outerHeight()-c.offset[0];d+=a.outerWidth()+c.offset[1];var i=b.outerHeight()+a.outerHeight();f=="center"&&(e+=i/2);f=="bottom"&&(e+=i);f=c.position[1];a=b.outerWidth()+a.outerWidth();f=="center"&&(d-=a/2);f=="left"&&(d-=a);return{top:e,left:d}}function n(a,b){var c=this,m=a.add(c),d,f=0,i=0,l=a.attr("title"),q=o[b.effect],j,r=a.is(":input"),n=r&&a.is(":checkbox, :radio, select, :button"),
s=a.attr("type"),k=b.events[s]||b.events[r?n?"widget":"input":"def"];if(!q)throw'Nonexistent effect "'+b.effect+'"';k=k.split(/,\s*/);if(k.length!=2)throw"Tooltip: bad events configuration for "+s;a.bind(k[0],function(a){b.predelay?(clearTimeout(f),i=setTimeout(function(){c.show(a)},b.predelay)):c.show(a)}).bind(k[1],function(a){b.delay?(clearTimeout(i),f=setTimeout(function(){c.hide(a)},b.delay)):c.hide(a)});l&&b.cancelDefault&&(a.removeAttr("title"),a.data("title",l));e.extend(c,{show:function(h){if(!d&&
(l?d=e(b.layout).addClass(b.tipClass).appendTo(document.body).hide():b.tip?d=e(b.tip).eq(0):(d=a.next(),d.length||(d=a.parent().next())),!d.length))throw"Cannot find tooltip for "+a;if(c.isShown())return c;d.stop(!0,!0);var g=p(a,d,b);l&&d.html(l);h=h||e.Event();h.type="onBeforeShow";m.trigger(h,[g]);if(h.isDefaultPrevented())return c;g=p(a,d,b);d.css({position:"absolute",top:g.top,left:g.left});j=!0;q[0].call(c,function(){h.type="onShow";j="full";m.trigger(h)});g=b.events.tooltip.split(/,\s*/);d.bind(g[0],
function(){clearTimeout(f);clearTimeout(i)});g[1]&&!a.is("input:not(:checkbox, :radio), textarea")&&d.bind(g[1],function(b){b.relatedTarget!=a[0]&&a.trigger(k[1].split(" ")[0])});return c},hide:function(a){if(!d||!c.isShown())return c;a=a||e.Event();a.type="onBeforeHide";m.trigger(a);if(!a.isDefaultPrevented())return j=!1,o[b.effect][1].call(c,function(){a.type="onHide";j=!1;m.trigger(a)}),c},isShown:function(a){return a?j=="full":j},getConf:function(){return b},getTip:function(){return d},getTrigger:function(){return a}});
e.each("onHide,onBeforeShow,onShow,onBeforeHide".split(","),function(a,d){e.isFunction(b[d])&&e(c).bind(d,b[d]);c[d]=function(a){e(c).bind(d,a);return c}})}e.tools=e.tools||{version:"1.2.0"};e.tools.tooltip={conf:{effect:"toggle",fadeOutSpeed:"fast",predelay:0,delay:30,opacity:1,tip:0,position:["top","center"],offset:[0,0],relative:!1,cancelDefault:!0,events:{def:"mouseenter,mouseleave",input:"focus,blur",widget:"focus mouseenter,blur mouseleave",tooltip:"mouseenter,mouseleave"},layout:"<div/>",tipClass:"tooltip"},
addEffect:function(a,b,c){o[a]=[b,c]}};var o={toggle:[function(a){var b=this.getConf(),c=this.getTip(),b=b.opacity;b<1&&c.css({opacity:b});c.show();a.call()},function(a){this.getTip().hide();a.call()}],fade:[function(a){this.getTip().fadeIn(this.getConf().fadeInSpeed,a)},function(a){this.getTip().fadeOut(this.getConf().fadeOutSpeed,a)}]};e.fn.tooltip=function(a){var b=this.data("tooltip");if(b)return b;a=e.extend(!0,{},e.tools.tooltip.conf,a);if(typeof a.position=="string")a.position=a.position.split(/,?\s/);
this.each(function(){b=new n(e(this),a);e(this).data("tooltip",b)});return a.api?b:this}})(jQuery);
