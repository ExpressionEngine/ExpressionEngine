/**
 * @license 
 * jQuery Tools 1.2.0 Scrollable - New wave UI design
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/scrollable.html
 *
 * Since: March 2008
 * Date:    Tue Apr 20 19:26:58 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.0 Scrollable - New wave UI design

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/scrollable.html

 Since: March 2008
 Date:    Tue Apr 20 19:26:58 2010 +0000 
*/
(function(e){function n(f,c){var a=e(c);return a.length<2?a:f.parent().find(c)}function t(f,c){var a=this,k=f.add(a),g=f.children(),l=0,m=c.vertical;j||(j=a);if(g.length>1)g=e(c.items,f);e.extend(a,{getConf:function(){return c},getIndex:function(){return l},getSize:function(){return a.getItems().size()},getNaviButtons:function(){return o.add(p)},getRoot:function(){return f},getItemWrap:function(){return g},getItems:function(){return g.children(c.item).not("."+c.clonedClass)},move:function(b,d){return a.seekTo(l+
b,d)},next:function(b){return a.move(1,b)},prev:function(b){return a.move(-1,b)},begin:function(b){return a.seekTo(0,b)},end:function(b){return a.seekTo(a.getSize()-1,b)},focus:function(){return j=a},addItem:function(b){b=e(b);if(c.circular){e(".cloned:last").before(b);e(".cloned:first").replaceWith(b.clone().addClass(c.clonedClass))}else g.append(b);k.trigger("onAddItem",[b]);return a},seekTo:function(b,d,h){if(!c.circular&&b<0||b>a.getSize())return a;var i=b;if(b.jquery)b=a.getItems().index(b);
else i=a.getItems().eq(b);var q=e.Event("onBeforeSeek");if(!h){k.trigger(q,[b,d]);if(q.isDefaultPrevented()||!i.length)return a}i=m?{top:-i.position().top}:{left:-i.position().left};g.animate(i,d,c.easing,h||function(){k.trigger("onSeek",[b])});j=a;l=b;return a}});e.each(["onBeforeSeek","onSeek","onAddItem"],function(b,d){e.isFunction(c[d])&&e(a).bind(d,c[d]);a[d]=function(h){e(a).bind(d,h);return a}});if(c.circular){var r=a.getItems().slice(-1).clone().prependTo(g),s=a.getItems().eq(1).clone().appendTo(g);
r.add(s).addClass(c.clonedClass);a.onBeforeSeek(function(b,d,h){if(!b.isDefaultPrevented())if(d==-1){a.seekTo(r,h,function(){a.end(0)});return b.preventDefault()}else d==a.getSize()&&a.seekTo(s,h,function(){a.begin(0)})});a.seekTo(0,0)}var o=n(f,c.prev).click(function(){a.prev()}),p=n(f,c.next).click(function(){a.next()});!c.circular&&a.getSize()>1&&a.onBeforeSeek(function(b,d){o.toggleClass(c.disabledClass,d<=0);p.toggleClass(c.disabledClass,d>=a.getSize()-1)});c.mousewheel&&e.fn.mousewheel&&f.mousewheel(function(b,
d){if(c.mousewheel){a.move(d<0?1:-1,c.wheelSpeed||50);return false}});c.keyboard&&e(document).bind("keydown.scrollable",function(b){if(!(!c.keyboard||b.altKey||b.ctrlKey||e(b.target).is(":input")))if(!(c.keyboard!="static"&&j!=a)){var d=b.keyCode;if(m&&(d==38||d==40)){a.move(d==38?-1:1);return b.preventDefault()}if(!m&&(d==37||d==39)){a.move(d==37?-1:1);return b.preventDefault()}}});e(a).trigger("onBeforeSeek",[c.initialIndex])}e.tools=e.tools||{version:"1.2.0"};e.tools.scrollable={conf:{activeClass:"active",
circular:false,clonedClass:"cloned",disabledClass:"disabled",easing:"swing",initialIndex:0,item:null,items:".items",keyboard:true,mousewheel:false,next:".next",prev:".prev",speed:400,vertical:false,wheelSpeed:0}};var j;e.fn.scrollable=function(f){var c=this.data("scrollable");if(c)return c;f=e.extend({},e.tools.scrollable.conf,f);this.each(function(){c=new t(e(this),f);e(this).data("scrollable",c)});return f.api?c:this}})(jQuery);
