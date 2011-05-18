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

(function(a){var c,b;a.ee_fileuploader=function(d){b=a.extend({},{},d);a.ee_filebrowser.endpoint_request("setup_upload",function(d){c=a(d.uploader).appendTo(document.body);c.removeClass().addClass("before_upload");b.type=="filemanager"?c.find(".button_bar .filebrowser").remove():b.type=="filebrowser"&&c.find(".button_bar .filemanager").remove();a(document).ready(function(){a.ee_fileuploader.build_dialog()});typeof b.load=="function"&&b.load.call(this,c)})};a.ee_fileuploader.build_dialog=function(){c.dialog({width:600,
height:300,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.fileuploader.window_title,autoOpen:!1,zIndex:99999,open:function(){e("before_upload");typeof b.open=="function"&&b.open.call(this,c);f()},close:function(){typeof b.close=="function"&&b.close.call(this,c,window.upload_iframe.file)}});a(b.trigger).live("click",function(a){a.preventDefault();c.dialog("open")})};var f=function(){a("#upload_file, #rename_file","#file_uploader .button_bar").click(function(d){d.preventDefault();
a("#file_uploader iframe").contents().find("form").submit()});a("#file_uploader .button_bar .cancel").live("click",function(a){a.preventDefault();c.dialog("close")})};a.ee_fileuploader.set_directory_id=function(a){if(!isNaN(parseInt(a,10))){var b=c.find("iframe").attr("src"),e=b.search("&directory_id=");e>0&&(b=b.substring(0,e));c.find("iframe").attr("src",b+"&directory_id="+a);return a}return!1};a.ee_fileuploader.file_exists=function(){e("file_exists")};a.ee_fileuploader.after_upload=function(d){typeof b.after_upload==
"function"&&b.after_upload.call(this,c,d);e("after_upload");b.type=="filemanager"?d.is_image?a("#file_uploader .button_bar #edit_file").show().click(function(){var b=a(".mainTable tr.new:first td:has(img) a[href*=edit_image]").attr("href");a(this).attr("href",b)}):a("#file_uploader .button_bar #edit_file").hide():b.type=="filebrowser"&&a("#file_uploader .button_bar #choose_file").click(function(b){b.preventDefault();c.dialog("close");a.ee_filebrowser.clean_up(d,"")})};var e=function(b){a("#file_uploader").removeClass("before_upload after_upload file_exists").addClass(b)}})(jQuery);
