/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(b){function k(){var a=b("#notice_counts");f={error:{count:0,last:"",counter:a.find(".notice_error").get(0)},alert:{count:0,last:"",counter:a.find(".notice_alert").get(0)},success:{count:0,last:"",counter:a.find(".notice_success").get(0)}};c=b("#notice_texts_container");a.find("span").click(q)}function g(){!b.browser!="safari"&&setTimeout(function(){window.scrollBy(0,1);window.scrollBy(0,-1)},15)}function l(a,c){if(f[a]){f[a].count=c;var d=f[a].counter;d.lastChild.nodeType==3&&d.removeChild(d.lastChild);
c==0?(f[a].last="",b(d).hide()):(d.innerHTML+="&nbsp;&nbsp;"+c,b(d).show(),g())}}function m(a){j!=a&&(c.find(".notice_texts").hide().end().find(".notice_"+a).show(),c.slideDown("fast",g),j=a,h&&n(h.counter));e.close_on_click&&!c.data("close_bound")&&(c.data("close_bound",!0),c.click(function(){c.one("mouseout",o)}))}function o(){c.slideUp("fast",function(){c.find(".notice_texts").html("");b.each(f,function(a){l(a,0)});b("#notice_flag").hide();b("#active_notice").attr("id","");j=!1})}function q(){var a=
this.className.substr(7);if(a=="info")return p();m(a);n(this);return!1}function n(a){b("#active_notice").attr("id","");a.id="active_notice"}var f={},p=function(){},h,c,e,j;b.ee_notice=function(a,i){c||k();i=i||{};if(b.isArray(a))b.each(a,function(a,c){b.ee_notice(c.message,b.extend(i,c))});else{e=b.extend({type:"notice",open:!1,close_on_click:!0},i);if(e.type=="notice")e.type="alert";if(h=f[e.type]){b("#notice_flag").css("display","inline");l(e.type,h.count+1);var d=b(".notice_texts.notice_"+e.type);
if(h.last==a){var d=d.children().slice(-1),g=d.find(".subcount");g.length?g.text(parseInt(g.text())+1):d.prepend('<span class="subcount">2</span>')}else d.append("<p>"+a+"</p>"),h.last=a}else if(e.type=="custom")b(".notice_texts.notice_custom").html(a);else throw"Invalid notification type.";(e.type=="error"||e.open)&&m(e.type);return b.ee_notice}};b.ee_notice.destroy=function(){c&&o()};b.ee_notice.show_info=function(a){c||k();b("#notice_flag").css("display","inline");b(".notice_info").show();p=a;
g()};b.ee_notice.hide_info=function(){b(".notice_info").hide();var a=0;b.each(f,function(b,c){a+=c.count});a||b("#notice_flag").hide()}})(jQuery);
