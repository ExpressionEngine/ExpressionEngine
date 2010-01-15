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
(function(d){var g={},h,a,m,b;function i(){var n=d("#notice_counts");g={error:{count:0,last:"",counter:n.find(".notice_error").get(0)},alert:{count:0,last:"",counter:n.find(".notice_alert").get(0)},success:{count:0,last:"",counter:n.find(".notice_success").get(0)}};a=d("#notice_texts_container");n.find("span").click(c)}d.ee_notice=function(q,r){if(!a){i()}if(d.isArray(q)){d.each(q,function(t,s){d.ee_notice(s.message,s)});return}m=d.extend({type:"notice",open:false},r);if(m.type=="notice"){m.type="alert"}h=g[m.type];if(h){e();var p=d(".notice_texts.notice_"+m.type);if(h.last==q){var o=p.children().slice(-1),n=o.find(".subcount");if(!n.length){o.prepend('<span class="subcount">'+2+"</span>")}else{n.text(parseInt(n.text())+1)}}else{p.append("<p>"+q+"</p>");h.last=q}}else{if(m.type=="custom"){d(".notice_texts.notice_custom").html(q)}else{throw"Invalid notification type."}}if(m.type=="error"||m.open){f(m.type)}return d.ee_notice};d.ee_notice.destroy=function(){if(a){j()}};function e(){d("#notice_flag").css("display","inline");k(m.type,h.count+1)}function k(o,p){if(!g[o]){return}g[o].count=p;var n=g[o].counter;if(n.lastChild.nodeType==3){n.removeChild(n.lastChild)}if(p==0){g[o].last="";d(n).hide()}else{d(n).show();n.innerHTML+="&nbsp;&nbsp;"+p}}function f(n){if(b!=n){a.find(".notice_texts").hide().end().find(".notice_"+n).show();a.slideDown("fast");b=n;if(h){l(h.counter)}}}function j(){a.slideUp("fast",function(){a.find(".notice_texts").html("");d.each(g,function(o,n){k(o,0)});d("#notice_flag").hide();d("#active_notice").attr("id","");b=false})}function c(){var n=this.className.substr(7);f(n);l(this);return false}function l(n){d("#active_notice").attr("id","");n.id="active_notice"}})(jQuery);