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

(function(a){var d,c,f=!0;a.ee_fileuploader=function(b){c=a.extend({},{},b);a.ee_filebrowser.endpoint_request("setup_upload",function(b){d=a(b.uploader).appendTo(document.body);d.removeClass().addClass("before_upload");c.type=="filemanager"?d.find(".button_bar .filebrowser").remove():c.type=="filebrowser"&&d.find(".button_bar .filemanager").remove();a(document).ready(function(){a.ee_fileuploader.build_dialog()});typeof c.load=="function"&&c.load.call(this,d)})};a.ee_fileuploader.build_dialog=function(){d.dialog({width:600,
height:300,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.fileuploader.window_title,autoOpen:!1,zIndex:99999,open:function(){e("before_upload");typeof c.open=="function"&&c.open.call(this,d);g()},close:function(){if(typeof window.upload_iframe.file!="undefined"){var b=window.upload_iframe.file;f&&a.ajax({url:EE.BASE+"&"+EE.fileuploader.delete_url,type:"POST",dataType:"json",data:{file:b.file_id,XID:EE.XID},error:function(a,b){console.log(b)}});typeof c.close=="function"&&
c.close.call(this,d,b)}}});a(c.trigger).live("click",function(b){b.preventDefault();d.dialog("open")})};var g=function(){a("#upload_file, #rename_file","#file_uploader .button_bar").click(function(b){b.preventDefault();a("#file_uploader iframe").contents().find("form").submit()});a("#file_uploader .button_bar .cancel").live("click",function(b){b.preventDefault();d.dialog("close")})};a.ee_fileuploader.set_directory_id=function(b){if(!isNaN(parseInt(b,10))){var a=d.find("iframe").attr("src"),c=a.search("&directory_id=");
c>0&&(a=a.substring(0,c));d.find("iframe").attr("src",a+"&directory_id="+b);return b}return!1};a.ee_fileuploader.file_exists=function(){e("file_exists")};a.ee_fileuploader.after_upload=function(b){f=!1;typeof c.after_upload=="function"&&c.after_upload.call(this,d,b);e("after_upload");c.type=="filemanager"?b.is_image?a("#file_uploader .button_bar #edit_file").show().click(function(){var b=a(".mainTable tr.new:first td:has(img) a[href*=edit_image]").attr("href");a(this).attr("href",b)}):a("#file_uploader .button_bar #edit_file").hide():
c.type=="filebrowser"&&a("#file_uploader .button_bar #choose_file").click(function(c){c.preventDefault();d.dialog("close");a.ee_filebrowser.clean_up(b,"")})};var e=function(b){a("#file_uploader").removeClass("before_upload after_upload file_exists").addClass(b)}})(jQuery);
