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
(function(c){var g=c.tools.scrollable;g.navigator={conf:{navi:".navi",naviItem:null,activeClass:"active",indexed:!1,idPrefix:null,history:!1}};c.fn.navigator=function(a){"string"==typeof a&&(a={navi:a});var a=c.extend({},g.navigator.conf,a),k;this.each(function(){function g(e,b,a){d.seekTo(b);if(i)location.hash&&(location.hash=e.attr("href").replace("#",""));else return a.preventDefault()}function f(){return h.find(a.naviItem||"> *")}function l(e){var b=c("<"+(a.naviItem||"a")+"/>").click(function(b){g(c(this),
e,b)}).attr("href","#"+e);0===e&&b.addClass(j);a.indexed&&b.text(e+1);a.idPrefix&&b.attr("id",a.idPrefix+e);return b.appendTo(h)}function m(a,b){var c=f().eq(b.replace("#",""));c.length||(c=f().filter("[href="+b+"]"));c.click()}var d=c(this).data("scrollable"),h,q=d.getRoot(),n=a.navi,p=c(n);h=2>p.length?p:q.parent().find(n);var r=d.getNaviButtons(),j=a.activeClass,i=a.history&&c.fn.history;d&&(k=d);d.getNaviButtons=function(){return r.add(h)};f().length?f().each(function(a){c(this).click(function(b){g(c(this),
a,b)})}):c.each(d.getItems(),function(a){l(a)});d.onBeforeSeek(function(a,b){var c=f().eq(b);!a.isDefaultPrevented()&&c.length&&f().removeClass(j).eq(b).addClass(j)});d.onAddItem(function(a,b){b=l(d.getItems().index(b));i&&b.history(m)});i&&f().history(m)});return a.api?k:this}})(jQuery);
