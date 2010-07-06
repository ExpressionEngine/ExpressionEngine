/*!
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
/*!
 * ExpressionEngine Async Upload Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
/* Usage Notes:
 *
 * Required parameter: url
 *
 * Sent GET data: iFrame id, custom data
 *
 * Expected Response:
 * A script tag that, when run, sets parent.EE_uploads.<iframeid> to the desired response string.
 * This response is automatically passed into the onComplete callback
 *
 * Events / Callbacks:
 * onStart		- called pre-request, return values are used to set request specific options
 * onComplete	- called once a response has been received
 * onFailure	- no implemented
 */
/* Example:
 *
 * $("input[type=file]").ee_upload({
 *     url: 'index.php?S=0&D=new&C=controller&M=upload_file',
 *     onStart: function(el) {
 *         return {additional: 'value'};
 *     },
 *     onComplete: function(response, element, options) {
 *         alert(response);
 *     }
 * });
 *
 * == Backend ==
 *
 * $additional = $this->input->get('additional');
 *
 * echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = "woot!";</script>';
 * exit;
 * 
 */
/* nothing */

(function(c){window.EE_uploads=window.EE_uploads||{};c.fn.ee_upload=function(j){var d;d={url:"",data:{},onStart:function(){return{}},onComplete:void 0,onFailure:void 0};c.extend(d,j);return this.each(function(){function g(){if(e.attr("type")=="file"){b={data:d.data};k();l()}}function k(){var a,f;a=Math.floor(Math.random()*99999);f=c('<iframe src="about:blank" style="display: none;" id="upload_target_'+a+'" name="upload_target_'+a+'"></iframe>');a=c('<form id="upload_form_'+a+'" action="#" method="post" enctype="multipart/form-data" style="display: none;"></form>');
f.load(function(){h(this)});c(document.body).append(f);c(document.body).append(a);b.iframe=f;b.form=a}function l(){b.iframe.load(function(){h(this)});b.form.submit(function(){var a=b.iframe.attr("id");c(this).attr("target",a);return true});e.change(m)}function m(){var a;a=d.onStart(e,d.data)||{};c.extend(b.data,a);b.form.attr("action",d.url+"&frame_id="+b.iframe.attr("id")+"&"+jQuery.param(b.data));var f,i;a=c("<div></div>");e.after(a);f=e.clone(true);i=e.remove();a.replaceWith(f);f.attr("value",
"");b.form.append(i);b.form.trigger("submit");e=f}function h(a){if(!jQuery.data(b.iframe,"upload_complete"))if((a.contentDocument?a.contentDocument:a.contentWindow?a.contentWindow.document:window.frames[id].document).location.href!="about:blank"){window.EE_uploads[b.iframe.attr("id")]?d.onComplete(window.EE_uploads[b.iframe.attr("id")],e,d.data):d.onFailure("Connection timed out. Please try again.",e,d.data);jQuery.data(b.iframe,"upload_complete",true);setTimeout(function(){b.iframe.remove();b.form.remove();
g()},0)}}var e,b;e=c(this);g()})}})(jQuery);
