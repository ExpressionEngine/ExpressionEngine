/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.4
 * @filesource
 */
// Fire off the file browser
$.ee_filebrowser(),
// Make sure we can create these methods without issues
EE.namespace("EE.publish.file_browser"),function(e){
// @todo rewrite dependencies and remove
function i(e){var i=!1;
// [![prompt]!], [![prompt:!:value]!]
return e?(e=e.toString(),e=e.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(e,i){var t=i.split("|!|");return altKey===!0?void 0!==t[1]?t[1]:t[0]:void 0===t[1]?"":t[0]}),e=e.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(e,t){var n=t.split(":!:");return i===!0?!1:(value=prompt(n[0],n[1]?n[1]:""),null===value&&(i=!0),value)})):""}/**
	 * Changes the hidden inputs, thumbnail and file name when a file is selected
	 * @private
	 * @param {Object} file File object with information about the file upload
	 * @param {Object} field jQuery object of the field
	 */
function t(i,t){var n=e("input[name='"+t+"']").closest(".file_field");0==i.is_image?n.find(".file_set").show().find(".filename").html('<img src="'+EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+i.file_name):n.find(".file_set").show().find(".filename").html('<img src="'+i.thumb+'" /><br />'+i.file_name),n.find(".choose_file").hide(),n.find(".undo_remove").hide(),n.find('input[name*="_hidden_file"]').val(i.file_name),n.find('input[name*="_hidden_dir"], select[name*="_directory"]').val(i.upload_location_id)}/**
	 * Given a selector and context, creates file browser triggers for multiple elements
	 * @private
	 * @param {String} selector The jQuery selector you're looking for,
	 *		representing the link to open the file browser
	 * @param {String} selector The jQuery selector representing the context in
	 *		which to search for the selector
	 */
function n(i,n){
// Look for every file input on the publish form and establish the
// file browser trigger. Also establishes the remove file handler.
e(i,n).each(function(){var i=e(this).closest(".file_field"),n=i.find(".choose_file"),l=i.find(".no_file"),o=e(this).data("content-type"),a=e(this).data("directory"),r=[],// used for undo
d={content_type:o,directory:a};e.ee_filebrowser.add_trigger(n,e(this).attr("name"),d,t),fileselector=n.length?n:l,i.find(".remove_file").click(function(){return i.find("input[type=hidden]").val(function(e,i){return r[e]=i,""}),i.find(".file_set").hide(),i.find(".sub_filename a").show(),fileselector.show(),!1}),i.find(".undo_remove").click(function(){return i.find("input[type=hidden]").val(function(e){return r.length?r[e]:""}),i.find(".file_set").show(),i.find(".sub_filename a").hide(),fileselector.hide(),!1})})}/**
	 * Fires up the filebrowser for text areas
	 */
EE.publish.file_browser.textarea=function(t){
// Bind the image html buttons
e.ee_filebrowser.add_trigger(e(".btn_img a, .file_manipulate",t),function(n){var l,o="",a="",r="",d="";button_id=e(this).parent().attr("class").match(/id(\d+)/),null!=button_id&&(button_id=button_id[1]),void 0!==t?(l=e("textarea",t),l.focus()):(
// A bit of working around various textareas, text inputs, tec
e(this).closest("#markItUpWrite_mode_textarea").length?textareaId="write_mode_textarea":textareaId=e(this).closest(".publish_field").attr("id").replace("hold_field_","field_id_"),void 0!=textareaId&&(l=e("textarea[name="+textareaId+"], input[name="+textareaId+"]",t),l.focus())),
// We also need to allow file insertion into text inputs (vs textareas) but markitup
// will not accommodate this, so we need to detect if this request is coming from a
// markitup button or another field type.
// Fact is - markitup is actually pretty crappy for anything that doesn't specifically
// use markitup. So currently the image button only works correctly on markitup textareas.
n.is_image?(a=EE.upload_directories[n.upload_location_id].properties,r=EE.upload_directories[n.upload_location_id].pre_format,d=EE.upload_directories[n.upload_location_id].post_format,image_tag=null==button_id?EE.filebrowser.image_tag:EE.filebrowser["image_tag_"+button_id],
// Include any user additions before or after the image link
o=image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+n.upload_location_id+"}"+n.file_name+'$2"'),
// Figure out dimensions
dimensions="","undefined"!=typeof n.file_hw_original&&""!=n.file_hw_original&&(dimensions=n.file_hw_original.split(" "),dimensions='height="'+dimensions[0]+'" width="'+dimensions[1]+'"'),o=o.replace(/\/?>$/,dimensions+" "+a+" />"),o=r+o+d):(a=EE.upload_directories[n.upload_location_id].file_properties,r=EE.upload_directories[n.upload_location_id].file_pre_format,r+='<a href="{filedir_'+n.upload_location_id+"}"+n.file_name+'" '+a+" >",d="</a>",d+=EE.upload_directories[n.upload_location_id].file_post_format),l.is("textarea")?(l.is(".markItUpEditor")||(l.markItUp(myNobuttonSettings),l.closest(".markItUpContainer").find(".markItUpHeader").hide(),l.focus()),
// Handle images and non-images differently
n.is_image?e.markItUp({replaceWith:o}):e.markItUp({key:"L",name:"Link",openWith:r,closeWith:d,placeHolder:n.file_name})):l.val(function(e,t){return t+=r+o+d,i(t)})})},/**
	 * Fire up the file browser for file fields
	 */
EE.publish.file_browser.file_field=function(){n("input[type=file]","#publishForm .publish_file, .pageContents"),
// Bind a new trigger when a new Grid row is added
Grid.bind("file","display",function(e){n("input[type=file]",e)})},/**
	 * Creates file browser trigger for the category edit modal
	 */
EE.publish.file_browser.category_edit_modal=function(){n("input[type=file]","#cat_modal_container")},e(function(){EE.filebrowser.publish&&
// Give Markitup time to activate
setTimeout(function(){EE.publish.file_browser.file_field(),EE.publish.file_browser.textarea()},15)})}(jQuery);