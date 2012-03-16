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
(function(d){function i(a){var b=!1;return a?(a=a.toString(),a=a.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(a,b){var c=b.split("|!|");return!0===altKey?void 0!==c[1]?c[1]:c[0]:void 0===c[1]?"":c[0]}),a=a.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(a,d){var c=d.split(":!:");if(!0===b)return!1;value=prompt(c[0],c[1]?c[1]:"");null===value&&(b=!0);return value})):""}function j(a,b){var e=d("input[name="+b+"]").parent().parent().parent();!1==a.is_image?e.find(".file_set").show().find(".filename").html('<img src="'+
EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+a.file_name):e.find(".file_set").show().find(".filename").html('<img src="'+a.thumb+'" /><br />'+a.file_name);d("input[name="+b+"_hidden]").val(a.file_name);d("select[name="+b+"_directory]").val(a.upload_location_id)}function h(a,b){d(a,b).each(function(){var a=d(this).parent().parent().parent(),b=a.find(".choose_file"),c=d(this).data("content-type"),f=d(this).data("directory"),c={content_type:c,directory:f};d.ee_filebrowser.add_trigger(b,
d(this).attr("name"),c,j);a.find(".remove_file").click(function(){a.find("input[type=hidden]").val("");a.find(".file_set").hide();return!1})})}EE.publish.file_browser.textarea=function(){d.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate",function(a){var b,e="",g="",c="",f="";textareaId=d(this).closest("#markItUpWrite_mode_textarea").length?"write_mode_textarea":d(this).closest(".publish_field").attr("id").replace("hold_field_","field_id_");void 0!=textareaId&&(b=d("#"+textareaId),b.focus());
a.is_image?(g=EE.upload_directories[a.upload_location_id].properties,c=EE.upload_directories[a.upload_location_id].pre_format,f=EE.upload_directories[a.upload_location_id].post_format,e=EE.filebrowser.image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+a.upload_location_id+"}"+a.file_name+'$2"'),dimensions="","undefined"!=typeof a.file_hw_original&&""!=a.file_hw_original&&(dimensions=a.file_hw_original.split(" "),dimensions='height="'+dimensions[0]+'" width="'+dimensions[1]+
'"'),e=e.replace(/\/?>$/,dimensions+" "+g+" />"),e=c+e+f):(g=EE.upload_directories[a.upload_location_id].file_properties,c=EE.upload_directories[a.upload_location_id].file_pre_format,c+='<a href="{filedir_'+a.upload_location_id+"}"+a.file_name+'" '+g+" >",f="</a>",f+=EE.upload_directories[a.upload_location_id].file_post_format);b.is("textarea")?(b.is(".markItUpEditor")||(b.markItUp(myNobuttonSettings),b.closest(".markItUpContainer").find(".markItUpHeader").hide(),b.focus()),a.is_image?d.markItUp({replaceWith:e}):
d.markItUp({key:"L",name:"Link",openWith:c,closeWith:f,placeHolder:a.file_name})):b.val(function(a,b){b+=c+e+f;return i(b)})})};EE.publish.file_browser.file_field=function(){h("input[type=file]","#publishForm, .pageContents")};EE.publish.file_browser.category_edit_modal=function(){h("input[type=file]","#cat_modal_container")};d(function(){!0==EE.filebrowser.publish&&setTimeout(function(){EE.publish.file_browser.file_field();EE.publish.file_browser.textarea()},15)})})(jQuery);
