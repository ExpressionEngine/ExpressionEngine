/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.4
 * @filesource
 */

$.ee_filebrowser();EE.namespace("EE.publish.file_browser");
(function(c){function i(a,b){var e=c("input[name="+b+"]").parent().parent().parent();!1==a.is_image?e.find(".file_set").show().find(".filename").html('<img src="'+EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+a.file_name):e.find(".file_set").show().find(".filename").html('<img src="'+a.thumb+'" /><br />'+a.file_name);c("input[name="+b+"_hidden]").val(a.file_name);c("input[name="+b+"_hidden_dir], select[name="+b+"_directory]").val(a.upload_location_id)}function h(a,
b){c(a,b).each(function(){var a=c(this).parent().parent().parent(),b=a.find(".choose_file"),d=c(this).data("content-type"),f=c(this).data("directory"),d={content_type:d,directory:f};c.ee_filebrowser.add_trigger(b,c(this).attr("name"),d,i);a.find(".remove_file").click(function(){a.find("input[type=hidden]").val("");a.find(".file_set").hide();return!1})})}EE.publish.file_browser.textarea=function(){c.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate",function(a){var b,e="",g="",d="",f="";button_id=
c(this).parent().attr("class").match(/id(\d+)/);null!=button_id&&(button_id=button_id[1]);textareaId=c(this).closest("#markItUpWrite_mode_textarea").length?"write_mode_textarea":c(this).closest(".publish_field").attr("id").replace("hold_field_","field_id_");void 0!=textareaId&&(b=c("#"+textareaId),b.focus());a.is_image?(g=EE.upload_directories[a.upload_location_id].properties,d=EE.upload_directories[a.upload_location_id].pre_format,f=EE.upload_directories[a.upload_location_id].post_format,image_tag=
null==button_id?EE.filebrowser.image_tag:EE.filebrowser["image_tag_"+button_id],e=image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+a.upload_location_id+"}"+a.file_name+'$2"'),dimensions="","undefined"!=typeof a.file_hw_original&&""!=a.file_hw_original&&(dimensions=a.file_hw_original.split(" "),dimensions='height="'+dimensions[0]+'" width="'+dimensions[1]+'"'),e=e.replace(/\/?>$/,dimensions+" "+g+" />"),e=d+e+f):(g=EE.upload_directories[a.upload_location_id].file_properties,
d=EE.upload_directories[a.upload_location_id].file_pre_format,d+='<a href="{filedir_'+a.upload_location_id+"}"+a.file_name+'" '+g+" >",f="</a>",f+=EE.upload_directories[a.upload_location_id].file_post_format);b.is("textarea")?(b.is(".markItUpEditor")||(b.markItUp(myNobuttonSettings),b.closest(".markItUpContainer").find(".markItUpHeader").hide(),b.focus()),a.is_image?c.markItUp({replaceWith:e}):c.markItUp({key:"L",name:"Link",openWith:d,closeWith:f,placeHolder:a.file_name})):b.val(function(a,c){var c=
c+(d+e+f),b;b=c;var g=!1;b?(b=b.toString(),b=b.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(a,b){var c=b.split("|!|");return!0===altKey?void 0!==c[1]?c[1]:c[0]:void 0===c[1]?"":c[0]}),b=b.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(a,b){var c=b.split(":!:");if(!0===g)return!1;value=prompt(c[0],c[1]?c[1]:"");null===value&&(g=!0);return value})):b="";return b})})};EE.publish.file_browser.file_field=function(){h("input[type=file]","#publishForm, .pageContents")};EE.publish.file_browser.category_edit_modal=
function(){h("input[type=file]","#cat_modal_container")};c(function(){!0==EE.filebrowser.publish&&setTimeout(function(){EE.publish.file_browser.file_field();EE.publish.file_browser.textarea()},15)})})(jQuery);
