/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(b){function l(){var a=b("#notice_counts");f={error:{count:0,last:"",counter:a.find(".notice_error").get(0)},alert:{count:0,last:"",counter:a.find(".notice_alert").get(0)},success:{count:0,last:"",counter:a.find(".notice_success").get(0)}};d=b("#notice_texts_container");a.find("span").click(s)}function i(){!b.browser!="safari"&&setTimeout(function(){window.scrollBy(0,1);window.scrollBy(0,-1)},15)}function m(a,g){if(f[a]){f[a].count=g;var c=f[a].counter;c.lastChild.nodeType==3&&c.removeChild(c.lastChild);
if(g==0){f[a].last="";b(c).hide()}else{c.innerHTML+="&nbsp;&nbsp;"+g;b(c).show();i()}}}function n(a){if(j!=a){d.find(".notice_texts").hide().end().find(".notice_"+a).show();d.slideDown("fast",i);j=a;h&&o(h.counter)}if(e.close_on_click)if(!d.data("close_bound")){d.data("close_bound",true);d.click(function(){d.one("mouseout",p)})}}function p(){d.slideUp("fast",function(){d.find(".notice_texts").html("");b.each(f,function(a){m(a,0)});b("#notice_flag").hide();b("#active_notice").attr("id","");j=false})}
function s(){var a=this.className.substr(7);if(a=="info")return q();n(a);o(this);return false}function o(a){b("#active_notice").attr("id","");a.id="active_notice"}var f={},q=function(){},h,d,e,j;b.ee_notice=function(a,g){d||l();if(b.isArray(a))b.each(a,function(t,r){b.ee_notice(r.message,r)});else{e=b.extend({type:"notice",open:false,close_on_click:true},g);if(e.type=="notice")e.type="alert";if(h=f[e.type]){b("#notice_flag").css("display","inline");m(e.type,h.count+1);var c=b(".notice_texts.notice_"+
e.type);if(h.last==a){c=c.children().slice(-1);var k=c.find(".subcount");k.length?k.text(parseInt(k.text())+1):c.prepend('<span class="subcount">2</span>')}else{c.append("<p>"+a+"</p>");h.last=a}}else if(e.type=="custom")b(".notice_texts.notice_custom").html(a);else throw"Invalid notification type.";if(e.type=="error"||e.open)n(e.type);return b.ee_notice}};b.ee_notice.destroy=function(){d&&p()};b.ee_notice.show_info=function(a){d||l();b("#notice_flag").css("display","inline");b(".notice_info").show();
q=a;i()};b.ee_notice.hide_info=function(){b(".notice_info").hide();var a=0;b.each(f,function(g,c){a+=c.count});a||b("#notice_flag").hide()}})(jQuery);
