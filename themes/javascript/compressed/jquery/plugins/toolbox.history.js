/**
 * @license 
 * jQuery Tools 1.2.0 History "Back button for AJAX apps"
 * 
 * NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * 
 * http://flowplayer.org/tools/toolbox/history.html
 * 
 * Since: Mar 2010
 * Date:    Tue Apr 20 19:26:58 2010 +0000 
 */

/*
 
 jQuery Tools 1.2.0 History "Back button for AJAX apps"

 NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.

 http://flowplayer.org/tools/toolbox/history.html

 Since: Mar 2010
 Date:    Tue Apr 20 19:26:58 2010 +0000 
*/
(function(a){function g(a){if(a){var b=d.contentWindow.document;b.open().close();b.location.hash=a}}var f,d,e,h;a.tools=a.tools||{version:"1.2.0"};a.tools.history={init:function(c){h||(a.browser.msie&&a.browser.version<"8"?d||(d=a("<iframe/>").attr("src","javascript:false;").hide().get(0),a("body").append(d),setInterval(function(){var b=d.contentWindow.document.location.hash;f!==b&&a.event.trigger("hash",b)},100),g(location.hash||"#")):setInterval(function(){var b=location.hash;b!==f&&a.event.trigger("hash",
b)},100),e=!e?c:e.add(c),c.click(function(b){var c=a(this).attr("href");d&&g(c);if(c.slice(0,1)!="#")return location.href="#"+c,b.preventDefault()}),h=!0)}};a(window).bind("hash",function(c,b){b?e.filter(function(){var c=a(this).attr("href");return c==b||c==b.replace("#","")}).trigger("history",[b]):e.eq(0).trigger("history",[b]);f=b});a.fn.history=function(c){a.tools.history.init(this);return this.bind("history",c)}})(jQuery);
