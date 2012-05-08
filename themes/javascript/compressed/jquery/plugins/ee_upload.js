/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(d){window.EE_uploads=window.EE_uploads||{};d.fn.ee_upload=function(n){var b,f;b={url:"",data:{},onStart:function(){return{}},onComplete:f,onFailure:f};d.extend(b,n);f=function(){};return this.each(function(){var e,c,f,i,g,j,k,l,m,h;e=d(this);g=function(){e.attr("type")==="file"&&(c={data:b.data},f(),i())};f=function(){var a,b;a=Math.floor(Math.random()*99999);b=d('<iframe src="about:blank" style="display: none;" id="upload_target_'+a+'" name="upload_target_'+a+'"></iframe>');a=d('<form id="upload_form_'+
a+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');b.load(function(){h(this)});d(document.body).append(b);d(document.body).append(a);c.iframe=b;c.form=a};i=function(){c.iframe.load(function(){h(this)});c.form.submit(function(){var a=c.iframe.attr("id");d(this).attr("target",a);return!0});e.change(k)};j=function(){var a,b,f;a=d("<div></div>");e.after(a);b=e.clone(!0);f=e.remove();a.replaceWith(b);b.attr("value","");c.form.append(f);c.form.trigger("submit");
e=b};k=function(){var a;a=b.onStart(e,b.data)||{};d.extend(c.data,a);c.form.attr("action",l());j()};l=function(){return b.url+"&frame_id="+c.iframe.attr("id")+"&"+jQuery.param(c.data)};m=function(){c.iframe.remove();c.form.remove();g()};h=function(a){if(!d(c.iframe).data("upload_complete")&&(a.contentDocument?a.contentDocument:a.contentWindow?a.contentWindow.document:window.frames[c.iframe.attr("id")].document).location.href!=="about:blank"){if(window.EE_uploads[c.iframe.attr("id")])b.onComplete(window.EE_uploads[c.iframe.attr("id")],
e,b.data);else b.onFailure("Connection timed out. Please try again.",e,b.data);d(c.iframe).data("upload_complete",!0);setTimeout(function(){m()},0)}};g()})}})(jQuery);
