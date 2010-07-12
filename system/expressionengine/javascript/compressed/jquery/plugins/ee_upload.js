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

(function(c){window.EE_uploads=window.EE_uploads||{};c.fn.ee_upload=function(j){var d;d={url:"",data:{},onStart:function(){return{}},onComplete:void 0,onFailure:void 0};c.extend(d,j);return this.each(function(){function g(){if(e.attr("type")=="file"){b={data:d.data};k();l()}}function k(){var a,f;a=Math.floor(Math.random()*99999);f=c('<iframe src="about:blank" style="display: none;" id="upload_target_'+a+'" name="upload_target_'+a+'"></iframe>');a=c('<form id="upload_form_'+a+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');
f.load(function(){h(this)});c(document.body).append(f);c(document.body).append(a);b.iframe=f;b.form=a}function l(){b.iframe.load(function(){h(this)});b.form.submit(function(){var a=b.iframe.attr("id");c(this).attr("target",a);return true});e.change(m)}function m(){var a;a=d.onStart(e,d.data)||{};c.extend(b.data,a);b.form.attr("action",d.url+"&frame_id="+b.iframe.attr("id")+"&"+jQuery.param(b.data));var f,i;a=c("<div></div>");e.after(a);f=e.clone(true);i=e.remove();a.replaceWith(f);f.attr("value",
"");b.form.append(i);b.form.trigger("submit");e=f}function h(a){if(!jQuery.data(b.iframe,"upload_complete"))if((a.contentDocument?a.contentDocument:a.contentWindow?a.contentWindow.document:window.frames[id].document).location.href!="about:blank"){window.EE_uploads[b.iframe.attr("id")]?d.onComplete(window.EE_uploads[b.iframe.attr("id")],e,d.data):d.onFailure("Connection timed out. Please try again.",e,d.data);jQuery.data(b.iframe,"upload_complete",true);setTimeout(function(){b.iframe.remove();b.form.remove();
g()},0)}}var e,b;e=c(this);g()})}})(jQuery);
