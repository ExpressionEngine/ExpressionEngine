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
(function(b){function n(a,f){var g=b(f);return g.length<2?g:a.parent().find(f)}var h=b.tools.scrollable;h.navigator={conf:{navi:".navi",naviItem:null,activeClass:"active",indexed:!1,idPrefix:null,history:!1}};b.fn.navigator=function(a){typeof a=="string"&&(a={navi:a});var a=b.extend({},h.navigator.conf,a),f;this.each(function(){function g(a,c,b){d.seekTo(c);if(j){if(location.hash)location.hash=a.attr("href").replace("#","")}else return b.preventDefault()}function e(){return k.find(a.naviItem||"> *")}
function h(i){var c=b("<"+(a.naviItem||"a")+"/>").click(function(a){g(b(this),i,a)}).attr("href","#"+i);i===0&&c.addClass(l);a.indexed&&c.text(i+1);a.idPrefix&&c.attr("id",a.idPrefix+i);return c.appendTo(k)}function m(a,c){var b=e().eq(c.replace("#",""));b.length||(b=e().filter("[href="+c+"]"));b.click()}var d=b(this).data("scrollable"),k=n(d.getRoot(),a.navi),o=d.getNaviButtons(),l=a.activeClass,j=a.history&&b.fn.history;d&&(f=d);d.getNaviButtons=function(){return o.add(k)};e().length?e().each(function(a){b(this).click(function(c){g(b(this),
a,c)})}):b.each(d.getItems(),function(a){h(a)});d.onBeforeSeek(function(a,c){var b=e().eq(c);!a.isDefaultPrevented()&&b.length&&e().removeClass(l).eq(c).addClass(l)});d.onAddItem(function(a,b){b=h(d.getItems().index(b));j&&b.history(m)});j&&e().history(m)});return a.api?f:this}})(jQuery);
