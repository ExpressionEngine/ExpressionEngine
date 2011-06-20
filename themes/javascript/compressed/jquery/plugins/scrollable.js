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
(function(d){function i(e,c){var a=d(c);return a.length<2?a:e.parent().find(c)}function r(e,c){var a=this,k=e.add(a),f=e.children(),l=0,m=c.vertical;j||(j=a);f.length>1&&(f=d(c.items,e));d.extend(a,{getConf:function(){return c},getIndex:function(){return l},getSize:function(){return a.getItems().size()},getNaviButtons:function(){return n.add(o)},getRoot:function(){return e},getItemWrap:function(){return f},getItems:function(){return f.children(c.item).not("."+c.clonedClass)},move:function(b,c){return a.seekTo(l+
b,c)},next:function(b){return a.move(1,b)},prev:function(b){return a.move(-1,b)},begin:function(b){return a.seekTo(0,b)},end:function(b){return a.seekTo(a.getSize()-1,b)},focus:function(){return j=a},addItem:function(b){b=d(b);c.circular?(d(".cloned:last").before(b),d(".cloned:first").replaceWith(b.clone().addClass(c.clonedClass))):f.append(b);k.trigger("onAddItem",[b]);return a},seekTo:function(b,g,e){if(!c.circular&&b<0||b>a.getSize())return a;var h=b;b.jquery?b=a.getItems().index(b):h=a.getItems().eq(b);
var i=d.Event("onBeforeSeek");if(!e&&(k.trigger(i,[b,g]),i.isDefaultPrevented()||!h.length))return a;h=m?{top:-h.position().top}:{left:-h.position().left};f.animate(h,g,c.easing,e||function(){k.trigger("onSeek",[b])});j=a;l=b;return a}});d.each(["onBeforeSeek","onSeek","onAddItem"],function(b,g){d.isFunction(c[g])&&d(a).bind(g,c[g]);a[g]=function(b){d(a).bind(g,b);return a}});if(c.circular){var p=a.getItems().slice(-1).clone().prependTo(f),q=a.getItems().eq(1).clone().appendTo(f);p.add(q).addClass(c.clonedClass);
a.onBeforeSeek(function(b,c,d){if(!b.isDefaultPrevented())if(c==-1)return a.seekTo(p,d,function(){a.end(0)}),b.preventDefault();else c==a.getSize()&&a.seekTo(q,d,function(){a.begin(0)})});a.seekTo(0,0)}var n=i(e,c.prev).click(function(){a.prev()}),o=i(e,c.next).click(function(){a.next()});if(!c.circular&&a.getSize()>1)a.onBeforeSeek(function(b,d){n.toggleClass(c.disabledClass,d<=0);o.toggleClass(c.disabledClass,d>=a.getSize()-1)});c.mousewheel&&d.fn.mousewheel&&e.mousewheel(function(b,d){if(c.mousewheel)return a.move(d<
0?1:-1,c.wheelSpeed||50),!1});c.keyboard&&d(document).bind("keydown.scrollable",function(b){if(c.keyboard&&!b.altKey&&!b.ctrlKey&&!d(b.target).is(":input")&&!(c.keyboard!="static"&&j!=a)){var e=b.keyCode;if(m&&(e==38||e==40))return a.move(e==38?-1:1),b.preventDefault();if(!m&&(e==37||e==39))return a.move(e==37?-1:1),b.preventDefault()}});d(a).trigger("onBeforeSeek",[c.initialIndex])}d.tools=d.tools||{version:"1.2.0"};d.tools.scrollable={conf:{activeClass:"active",circular:!1,clonedClass:"cloned",
disabledClass:"disabled",easing:"swing",initialIndex:0,item:null,items:".items",keyboard:!0,mousewheel:!1,next:".next",prev:".prev",speed:400,vertical:!1,wheelSpeed:0}};var j;d.fn.scrollable=function(e){var c=this.data("scrollable");if(c)return c;e=d.extend({},d.tools.scrollable.conf,e);this.each(function(){c=new r(d(this),e);d(this).data("scrollable",c)});return e.api?c:this}})(jQuery);
