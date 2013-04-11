/**
 * @license 
 * jQuery Tools 1.2.3 / Overlay Apple effect. 
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/overlay/apple.html
 *
 * Since: July 2009
 * Date:    Mon Jun 7 13:43:53 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.3 / Overlay Apple effect. 

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/overlay/apple.html

 Since: July 2009
 Date:    Mon Jun 7 13:43:53 2010 +0000 
*/
(function(i){function k(a){var d=a.offset();return{top:d.top+a.height()/2,left:d.left+a.width()/2}}var j=i.tools.overlay,f=i(window);i.extend(j.conf,{start:{top:null,left:null},fadeInSpeed:"fast",zIndex:9999});j.addEffect("apple",function(a,d){var b=this.getOverlay(),c=this.getConf(),g=this.getTrigger(),j=this,l=b.outerWidth({margin:!0}),h=b.data("img");if(!h){var e=b.css("backgroundImage");if(!e)throw"background-image CSS property not set for overlay";e=e.slice(e.indexOf("(")+1,e.indexOf(")")).replace(/\"/g,
"");b.css("backgroundImage","none");h=i('<img src="'+e+'"/>');h.css({border:0,display:"none"}).width(l);i("body").append(h);b.data("img",h)}var e=c.start.top||Math.round(f.height()/2),m=c.start.left||Math.round(f.width()/2);if(g)g=k(g),e=g.top,m=g.left;h.css({position:"absolute",top:e,left:m,width:0,zIndex:c.zIndex}).show();a.top+=f.scrollTop();a.left+=f.scrollLeft();a.position="absolute";b.css(a);h.animate({top:b.css("top"),left:b.css("left"),width:l},c.speed,function(){if(c.fixed)a.top-=f.scrollTop(),
a.left-=f.scrollLeft(),a.position="fixed",h.add(b).css(a);b.css("zIndex",c.zIndex+1).fadeIn(c.fadeInSpeed,function(){j.isOpened()&&!i(this).index(b)?d.call():b.hide()})})},function(a){var d=this.getOverlay().hide(),b=this.getConf(),c=this.getTrigger(),d=d.data("img"),g={top:b.start.top,left:b.start.left,width:0};c&&i.extend(g,k(c));b.fixed&&d.css({position:"absolute"}).animate({top:"+="+f.scrollTop(),left:"+="+f.scrollLeft()},0);d.animate(g,b.closeSpeed,a)})})(jQuery);
