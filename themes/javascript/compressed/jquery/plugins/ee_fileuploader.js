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

(function(a){var b;a.ee_fileuploader=function(){a.ee_filebrowser.endpoint_request("setup_upload",function(c){b=a(c.uploader).appendTo(document.body);b.removeClass("upload_step_2").addClass("upload_step_1");a(document).ready(function(){a.ee_fileuploader.build_dialog()})})};a.ee_fileuploader.build_dialog=function(){b.dialog({width:600,height:300,resizable:false,position:["center","center"],modal:true,draggable:true,title:EE.fileuploader.window_title,autoOpen:false,zIndex:99999,open:function(){var c=
a("#dir_choice").val(),d=b.find("iframe").attr("src"),e=d.search("&directory_id=");if(e>0)d=d.substring(0,e);b.find("iframe").attr("src",d+"&directory_id="+c);f()},close:function(){a.ee_filebrowser.reload_directory(a("#dir_choice").val())}});a("#fileChooser #upload_form input").live("click",function(){b.dialog("open")})};var f=function(){a("#file_uploader .button_bar #upload_file").click(function(c){c.preventDefault();a("#file_uploader iframe").contents().find("form").submit()})};a.ee_fileuploader.place_file=
function(c){a("#file_uploader").removeClass("upload_step_1").addClass("upload_step_2");a("#file_uploader .button_bar #choose_file").click(function(d){d.preventDefault();a("#file_uploader").removeClass("upload_step_2").addClass("upload_step_1");b.dialog("close");a.ee_filebrowser.clean_up(c,"")})}})(jQuery);
