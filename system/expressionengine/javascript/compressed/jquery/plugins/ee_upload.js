/*
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
/*
 * ExpressionEngine Async Upload Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
(function(a){window.EE_uploads=window.EE_uploads||{};a.fn.ee_upload=function(d){var b;b={url:"",data:{},onStart:function(){return{}},onComplete:c,onFailure:c};a.extend(b,d);return this.each(function(){var k,i;k=a(this);function h(){if(k.attr("type")!="file"){return}i={data:b.data};m();j()}function m(){var q,o,p;q=Math.floor(Math.random()*99999);o=a('<iframe src="about:blank" style="display: none;" id="upload_target_'+q+'" name="upload_target_'+q+'"></iframe>');p=a('<form id="upload_form_'+q+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');o.load(function(){l(this)});a(document.body).append(o);a(document.body).append(p);i.iframe=o;i.form=p}function j(){i.iframe.load(function(){l(this)});i.form.submit(function(){var o=i.iframe.attr("id");a(this).attr("target",o);return true});k.change(f)}function f(){var o;o=b.onStart(k,b.data)||{};a.extend(i.data,o);i.form.attr("action",n());g()}function n(){return b.url+"&frame_id="+i.iframe.attr("id")+"&"+jQuery.param(i.data)}function g(){var q,o,p;q=a("<div></div>");k.after(q);o=k.clone(true);p=k.remove();q.replaceWith(o);o.attr("value","");i.form.append(p);i.form.trigger("submit");k=o}function l(p){if(jQuery.data(i.iframe,"upload_complete")){return}if(p.contentDocument){var o=p.contentDocument}else{if(p.contentWindow){var o=p.contentWindow.document}else{var o=window.frames[id].document}}if(o.location.href=="about:blank"){return}if(window.EE_uploads[i.iframe.attr("id")]){b.onComplete(window.EE_uploads[i.iframe.attr("id")],k,b.data)}else{b.onFailure("Connection timed out. Please try again.",k,b.data)}jQuery.data(i.iframe,"upload_complete",true);setTimeout(function(){e()},0)}function e(){i.iframe.remove();i.form.remove();h()}h()});var c=function(){}}})(jQuery);