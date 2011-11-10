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

(function(d){window.EE_uploads=window.EE_uploads||{};d.fn.ee_upload=function(p){var b,f;b={url:"",data:{},onStart:function(){return{}},onComplete:f,onFailure:f};d.extend(b,p);f=function(){};return this.each(function(){var f,c,g,e,j,k,h,l,m,n,o,i;e=d(this);h=function(){if(e.attr("type")==="file")f=b.data,c=void 0,g=void 0,j(),k()};j=function(){var a,b;a=Math.floor(Math.random()*99999);b=d('<iframe src="about:blank" style="display: none;" id="upload_target_'+a+'" name="upload_target_'+a+'"></iframe>');
a=d('<form id="upload_form_'+a+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');b.load(function(){i(this)});d(document.body).append(b);d(document.body).append(a);c=b;g=a};k=function(){c.load(function(){i(this)});g.submit(function(){var a=c.attr("id");d(this).attr("target",a);return!0});e.change(m)};l=function(){var a,b,c;a=d("<div></div>");e.after(a);b=e.clone(!0);c=e.remove();a.replaceWith(b);b.attr("value","");g.append(c);g.trigger("submit");e=b};m=function(){var a;
a=b.onStart(e,b.data)||{};d.extend(f,a);g.attr("action",n());l()};n=function(){return b.url+"&frame_id="+c.attr("id")+"&"+jQuery.param(f)};o=function(){c.remove();g.remove();h()};i=function(a){if(!d(c).data("upload_complete")&&(a.contentDocument?a.contentDocument:a.contentWindow?a.contentWindow.document:window.frames[c.attr("id")].document).location.href!=="about:blank"){if(window.EE_uploads[c.attr("id")])b.onComplete(window.EE_uploads[c.attr("id")],e,b.data);else b.onFailure("Connection timed out. Please try again.",
e,b.data);d(c).data("upload_complete",!0);setTimeout(function(){o()},0)}};h()})}})(jQuery);
