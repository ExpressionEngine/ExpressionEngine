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

(function(c){window.EE_uploads=window.EE_uploads||{};c.fn.ee_upload=function(q){var d,g;d={url:"",data:{},onStart:function(){return{}},onComplete:g,onFailure:g};c.extend(d,q);g=function(){};return this.each(function(){var e,b,j,k,h,l,m,n,o,i;e=c(this);h=function(){if(e.attr("type")==="file"){b={data:d.data};j();k()}};j=function(){var a,f;a=Math.floor(Math.random()*99999);f=c('<iframe src="about:blank" style="display: none;" id="upload_target_'+a+'" name="upload_target_'+a+'"></iframe>');a=c('<form id="upload_form_'+
a+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');f.load(function(){i(this)});c(document.body).append(f);c(document.body).append(a);b.iframe=f;b.form=a};k=function(){b.iframe.load(function(){i(this)});b.form.submit(function(){var a=b.iframe.attr("id");c(this).attr("target",a);return true});e.change(m)};l=function(){var a,f,p;a=c("<div></div>");e.after(a);f=e.clone(true);p=e.remove();a.replaceWith(f);f.attr("value","");b.form.append(p);b.form.trigger("submit");
e=f};m=function(){var a;a=d.onStart(e,d.data)||{};c.extend(b.data,a);b.form.attr("action",n());l()};n=function(){return d.url+"&frame_id="+b.iframe.attr("id")+"&"+jQuery.param(b.data)};o=function(){b.iframe.remove();b.form.remove();h()};i=function(a){if(!c(b.iframe).data("upload_complete"))if((a.contentDocument?a.contentDocument:a.contentWindow?a.contentWindow.document:window.frames[b.iframe.attr("id")].document).location.href!=="about:blank"){window.EE_uploads[b.iframe.attr("id")]?d.onComplete(window.EE_uploads[b.iframe.attr("id")],
e,d.data):d.onFailure("Connection timed out. Please try again.",e,d.data);c(b.iframe).data("upload_complete",true);setTimeout(function(){o()},0)}};h()})}})(jQuery);
