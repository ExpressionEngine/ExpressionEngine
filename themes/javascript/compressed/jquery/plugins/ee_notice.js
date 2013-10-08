/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

(function(b){function m(){var a=b("#notice_counts");f={error:{count:0,last:"",counter:a.find(".notice_error").get(0)},alert:{count:0,last:"",counter:a.find(".notice_alert").get(0)},success:{count:0,last:"",counter:a.find(".notice_success").get(0)}};c=b("#notice_texts_container");a.find("span").click(t)}function g(){"safari"!=!b.browser&&setTimeout(function(){window.scrollBy(0,1);window.scrollBy(0,-1)},15)}function n(a,c){if(f[a]){f[a].count=c;var d=f[a].counter;3==d.lastChild.nodeType&&d.removeChild(d.lastChild);
0==c?(f[a].last="",b(d).hide()):(d.innerHTML+="&nbsp;&nbsp;"+c,b(d).show(),g())}}function p(a){l!=a&&(c.find(".notice_texts").hide().end().find(".notice_"+a).show(),c.slideDown("fast",g),l=a,h&&q(h.counter));e.close_on_click&&!c.data("close_bound")&&(c.data("close_bound",!0),c.click(function(){c.one("mouseout",r)}))}function r(){c.slideUp("fast",function(){c.find(".notice_texts").html("");b.each(f,function(a,b){n(a,0)});b("#notice_flag").hide();b("#active_notice").attr("id","");l=!1})}function t(){var a=
this.className.substr(7);if("info"==a)return s();p(a);q(this);return!1}function q(a){b("#active_notice").attr("id","");a.id="active_notice"}var f={},s=function(){},h,c,e,l;b.ee_notice=function(a,k){c||m();k=k||{};if(b.isArray(a))b.each(a,function(a,c){b.ee_notice(c.message,b.extend(k,c))});else{e=b.extend({type:"notice",open:!1,close_on_click:!0},k);"notice"==e.type&&(e.type="alert");if(h=f[e.type]){b("#notice_flag").css("display","inline");n(e.type,h.count+1);var d=b(".notice_texts.notice_"+e.type);
if(h.last==a){var d=d.children().slice(-1),g=d.find(".subcount");g.length?g.text(parseInt(g.text())+1):d.prepend('<span class="subcount">2</span>')}else d.append("<p>"+a+"</p>"),h.last=a}else if("custom"==e.type)b(".notice_texts.notice_custom").html(a);else throw"Invalid notification type.";("error"==e.type||e.open)&&p(e.type);return b.ee_notice}};b.ee_notice.destroy=function(){c&&r()};b.ee_notice.show_info=function(a){c||m();b("#notice_flag").css("display","inline");b(".notice_info").show();s=a;
g()};b.ee_notice.hide_info=function(){b(".notice_info").hide();var a=0;b.each(f,function(b,c){a+=c.count});a||b("#notice_flag").hide()}})(jQuery);
