/**
 * @license 
 * jQuery Tools 1.2.2 / Scrollable Autoscroll
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/scrollable/autoscroll.html
 *
 * Since: September 2009
 * Date:    Wed May 19 06:53:17 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.2 / Scrollable Autoscroll

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/scrollable/autoscroll.html

 Since: September 2009
 Date:    Wed May 19 06:53:17 2010 +0000 
*/
(function(e){var d=e.tools.scrollable;d.autoscroll={conf:{autoplay:!0,interval:3E3,autopause:!0}};e.fn.autoscroll=function(b){typeof b=="number"&&(b={interval:b});var c=e.extend({},d.autoscroll.conf,b),g;this.each(function(){var a=e(this).data("scrollable");a&&(g=a);var b,d,f=!0;a.play=function(){b||(f=!1,b=setInterval(function(){a.next()},c.interval),a.next())};a.pause=function(){b=clearInterval(b)};a.stop=function(){a.pause();f=!0};c.autopause&&a.getRoot().add(a.getNaviButtons()).hover(function(){a.pause();
clearInterval(d)},function(){f||(d=setTimeout(a.play,c.interval))});c.autoplay&&setTimeout(a.play,c.interval)});return c.api?g:this}})(jQuery);
