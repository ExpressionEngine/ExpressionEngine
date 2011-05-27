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

(function(a){var c,b,d,g=!0;a.ee_fileuploader=function(e){b=a.extend({},{},e);a.ee_filebrowser.endpoint_request("setup_upload",function(e){c=a(e.uploader).appendTo(document.body);c.removeClass().addClass("before_upload");b.type=="filemanager"?c.find(".button_bar .filebrowser").remove():b.type=="filebrowser"&&c.find(".button_bar .filemanager").remove();a(document).ready(function(){a.ee_fileuploader.build_dialog()});typeof b.load=="function"&&b.load.call(this,c)})};a.ee_fileuploader.build_dialog=function(){c.dialog({width:600,
height:370,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.fileuploader.window_title,autoOpen:!1,zIndex:99999,open:function(){f("before_upload");d={};typeof b.open=="function"&&b.open.call(this,c);h()},close:function(){typeof window.upload_iframe.file!="undefined"&&(g&&a.ajax({url:EE.BASE+"&"+EE.fileuploader.delete_url,type:"POST",dataType:"json",data:{file:d.file_id,XID:EE.XID},error:function(a,c){console.log(c)}}),typeof b.close=="function"&&b.close.call(this,c,d))}});a(b.trigger).live("click",
function(a){a.preventDefault();c.dialog("open")})};var h=function(){a("#upload_file, #rename_file","#file_uploader .button_bar").click(function(e){e.preventDefault();a("#file_uploader iframe").contents().find("form").submit()});a("#file_uploader .button_bar .cancel").live("click",function(a){a.preventDefault();c.dialog("close")})};a.ee_fileuploader.set_directory_id=function(a){if(!isNaN(parseInt(a,10))){var b=c.find("iframe").attr("src"),d=b.search("&directory_id=");d>0&&(b=b.substring(0,d));c.find("iframe").attr("src",
b+"&directory_id="+a);return a}return!1};a.ee_fileuploader.file_exists=function(b){a.ee_fileuploader.update_file(b);f("file_exists")};a.ee_fileuploader.after_upload=function(e){a.ee_fileuploader.update_file(e);g=!1;typeof b.after_upload=="function"&&b.after_upload.call(this,c,d);f("after_upload");b.type=="filemanager"?e.is_image?a("#file_uploader .button_bar #edit_file").unbind().show().click(function(){var b=a(".mainTable tr.new:first td:has(img) a[href*=edit_image]").attr("href");a(this).attr("href",
b)}):a("#file_uploader .button_bar #edit_file").hide():b.type=="filebrowser"&&(a("#file_uploader .button_bar #choose_file").unbind().one("click",function(b){b.preventDefault();c.dialog("close");a.ee_filebrowser.clean_up(d,"")}),a("#file_uploader .button_bar #edit_file_modal").unbind().show().one("click",function(b){b.preventDefault();a("#file_uploader iframe").contents().find("form#resize_rotate").submit();a(this).hide()}))};a.ee_fileuploader.update_file=function(a){d=a};var f=function(b){a("#file_uploader").removeClass("before_upload after_upload file_exists").addClass(b)}})(jQuery);
