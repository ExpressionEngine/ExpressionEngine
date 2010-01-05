/*
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
/*
 * ExpressionEngine JS Notification Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
(function(g){var j=[],o=/\bjs_.*?\b/g,f=false,n,a,b,i,e,h;g.ee_notice=function(p,q){n=g.extend({type:"notice",delay:0,duration:3000,animation_speed:400},q);if(j.length){f=true}if(typeof p=="string"){p=[{message:p}]}p=g.map(p,function(r){r.type=r.type||n.type;r.duration=r.duration||(r.type=="error")?0:n.duration;return r});j.push.apply(j,p);if(f===false){f=true;setTimeout(function(){k();d()},n.delay)}return g.ee_notice};g.ee_notice.destroy=function(){j=[];if(i){c()}};function k(){if(!i){var p=g('<div class="close_handle"><a href="#">&times;</a></div>');e=g("<span/>");h=g('<div class="notice_inner"/>').append(e,p);i=g('<div class="js_notification"/>').append(h).appendTo(document.body);p.click(c);i.hover(function(){m(500)},l);i.click(function(){m(1)})}}function m(p){if(typeof a=="number"){window.clearTimeout(a)}if(p&&j[0].duration){j[0].duration=p}}function l(){m();if(j.length&&j[0].duration){a=window.setTimeout(c,j[0].duration)}}function d(){f=false;if(!j.length){return m()}h[0].className=h[0].className.replace(o,"");h.addClass("js_"+j[0].type);e.html(j[0].message);b=i.outerHeight();i.css("top",-b);i.show().animate({top:0},n.animation_speed,l)}function c(){j=j.slice(1);i.animate({top:-b},n.animation_speed,d);return false}})(jQuery);