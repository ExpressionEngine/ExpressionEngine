/**
 * @license 
 * jQuery Tools 1.2.2 / Scrollable Navigator
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 *
 * http://flowplayer.org/tools/scrollable/navigator.html
 *
 * Since: September 2009
 * Date:    Wed May 19 06:53:17 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.2 / Scrollable Navigator

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/scrollable/navigator.html

 Since: September 2009
 Date:    Wed May 19 06:53:17 2010 +0000 
*/
(function(d){function p(b,h){var i=d(h);return i.length<2?i:b.parent().find(h)}var m=d.tools.scrollable;m.navigator={conf:{navi:".navi",naviItem:null,activeClass:"active",indexed:false,idPrefix:null,history:false}};d.fn.navigator=function(b){if(typeof b=="string")b={navi:b};b=d.extend({},m.navigator.conf,b);var h;this.each(function(){function i(c,a,f){e.seekTo(a);if(j){if(location.hash)location.hash=c.attr("href").replace("#","")}else return f.preventDefault()}function g(){return k.find(b.naviItem||
"> *")}function n(c){var a=d("<"+(b.naviItem||"a")+"/>").click(function(f){i(d(this),c,f)}).attr("href","#"+c);c===0&&a.addClass(l);b.indexed&&a.text(c+1);b.idPrefix&&a.attr("id",b.idPrefix+c);return a.appendTo(k)}function o(c,a){var f=g().eq(a.replace("#",""));f.length||(f=g().filter("[href="+a+"]"));f.click()}var e=d(this).data("scrollable"),k=p(e.getRoot(),b.navi),q=e.getNaviButtons(),l=b.activeClass,j=b.history&&d.fn.history;if(e)h=e;e.getNaviButtons=function(){return q.add(k)};g().length?g().each(function(c){d(this).click(function(a){i(d(this),
c,a)})}):d.each(e.getItems(),function(c){n(c)});e.onBeforeSeek(function(c,a){var f=g().eq(a);!c.isDefaultPrevented()&&f.length&&g().removeClass(l).eq(a).addClass(l)});e.onAddItem(function(c,a){a=n(e.getItems().index(a));j&&a.history(o)});j&&g().history(o)});return b.api?h:this}})(jQuery);
