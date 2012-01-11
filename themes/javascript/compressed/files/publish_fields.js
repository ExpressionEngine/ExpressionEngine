/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.4
 * @filesource
 */

$.ee_filebrowser();EE.namespace("EE.publish.file_browser");
(function(b){function i(a,c){var d=b("input[name="+c+"]").parent().parent().parent();a.is_image==!1?d.find(".file_set").show().find(".filename").html('<img src="'+EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+a.file_name):d.find(".file_set").show().find(".filename").html('<img src="'+a.thumb+'" /><br />'+a.file_name);b("input[name="+c+"_hidden]").val(a.file_name);b("select[name="+c+"_directory]").val(a.upload_location_id)}function h(a,c){b(a,c).each(function(){var a=
b(this).parent().parent().parent(),c=a.find(".choose_file"),e=b(this).data("content-type"),f=b(this).data("directory"),e={content_type:e,directory:f};b.ee_filebrowser.add_trigger(c,b(this).attr("name"),e,i);a.find(".remove_file").click(function(){a.find("input[type=hidden]").val("");a.find(".file_set").hide();return!1})})}EE.publish.file_browser.textarea=function(){b.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate",function(a){var c,d="",g="",e="",f="";textareaId=b(this).closest("#markItUpWrite_mode_textarea").length?
"write_mode_textarea":b(this).closest(".publish_field").attr("id").replace("hold_field_","field_id_");textareaId!=void 0&&(c=b("#"+textareaId),c.focus());a.is_image?(g=EE.upload_directories[a.upload_location_id].properties,e=EE.upload_directories[a.upload_location_id].pre_format,f=EE.upload_directories[a.upload_location_id].post_format,d=EE.filebrowser.image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+a.upload_location_id+"}"+a.file_name+'$2"'),dimensions="",typeof a.file_hw_original!=
"undefined"&&a.file_hw_original!=""&&(dimensions=a.file_hw_original.split(" "),dimensions='height="'+dimensions[0]+'" width="'+dimensions[1]+'"'),d=d.replace(/\/?>$/,dimensions+" "+g+" />"),d=e+d+f):(g=EE.upload_directories[a.upload_location_id].file_properties,e=EE.upload_directories[a.upload_location_id].file_pre_format,e+='<a href="{filedir_'+a.upload_location_id+"}"+a.file_name+'" '+g+" >",f="</a>",f+=EE.upload_directories[a.upload_location_id].file_post_format);c.is("textarea")?(c.is(".markItUpEditor")||
(c.markItUp(myNobuttonSettings),c.closest(".markItUpContainer").find(".markItUpHeader").hide(),c.focus()),a.is_image?b.markItUp({replaceWith:d}):b.markItUp({key:"L",name:"Link",openWith:e,closeWith:f,placeHolder:a.file_name})):c.val(function(a,b){b+=e+d+f;return magicMarkups(b)})})};EE.publish.file_browser.file_field=function(){h("input[type=file]","#publishForm, .pageContents")};EE.publish.file_browser.category_edit_modal=function(){h("input[type=file]","#cat_modal_container")};b(function(){EE.filebrowser.publish==
!0&&setTimeout(function(){EE.publish.file_browser.file_field();EE.publish.file_browser.textarea()},15)})})(jQuery);
