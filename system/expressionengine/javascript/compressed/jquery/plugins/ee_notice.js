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
(function(f){var h={},k=function(){},i,b,o,c;function j(){var p=f("#notice_counts");h={error:{count:0,last:"",counter:p.find(".notice_error").get(0)},alert:{count:0,last:"",counter:p.find(".notice_alert").get(0)},success:{count:0,last:"",counter:p.find(".notice_success").get(0)}};b=f("#notice_texts_container");p.find("span").click(d)}f.ee_notice=function(s,t){if(!b){j()}if(f.isArray(s)){f.each(s,function(w,u){f.ee_notice(u.message,u)});return}o=f.extend({type:"notice",open:false},t);if(o.type=="notice"){o.type="alert"}i=h[o.type];if(i){e();var r=f(".notice_texts.notice_"+o.type);if(i.last==s){var q=r.children().slice(-1),p=q.find(".subcount");if(!p.length){q.prepend('<span class="subcount">'+2+"</span>")}else{p.text(parseInt(p.text())+1)}}else{r.append("<p>"+s+"</p>");i.last=s}}else{if(o.type=="custom"){f(".notice_texts.notice_custom").html(s)}else{throw"Invalid notification type."}}if(o.type=="error"||o.open){g(o.type)}return f.ee_notice};f.ee_notice.destroy=function(){if(b){l()}};f.ee_notice.show_info=function(p){if(!b){j()}f("#notice_flag").css("display","inline");f(".notice_info").show();k=p;a()};f.ee_notice.hide_info=function(){f(".notice_info").hide();var p=0;f.each(h,function(r,q){p+=q.count});if(!p){f("#notice_flag").hide()}};function a(){if(!f.browser=="safari"){return}setTimeout(function(){window.scrollBy(0,1);window.scrollBy(0,-1)},15)}function e(){f("#notice_flag").css("display","inline");m(o.type,i.count+1)}function m(q,r){if(!h[q]){return}h[q].count=r;var p=h[q].counter;if(p.lastChild.nodeType==3){p.removeChild(p.lastChild)}if(r==0){h[q].last="";f(p).hide()}else{p.innerHTML+="&nbsp;&nbsp;"+r;f(p).show();a()}}function g(p){if(c!=p){b.find(".notice_texts").hide().end().find(".notice_"+p).show();b.slideDown("fast");c=p;if(i){n(i.counter)}}}function l(){b.slideUp("fast",function(){b.find(".notice_texts").html("");f.each(h,function(q,p){m(q,0)});f("#notice_flag").hide();f("#active_notice").attr("id","");c=false})}function d(){var p=this.className.substr(7);if(p=="info"){return k()}g(p);n(this);return false}function n(p){f("#active_notice").attr("id","");p.id="active_notice"}})(jQuery);