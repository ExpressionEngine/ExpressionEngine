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
(function(b){function l(a){var b=!1;return a?(a=a.toString(),a=a.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(a,b){var c=b.split("|!|");return!0===altKey?void 0!==c[1]?c[1]:c[0]:void 0===c[1]?"":c[0]}),a=a.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(a,g){var c=g.split(":!:");if(!0===b)return!1;value=prompt(c[0],c[1]?c[1]:"");null===value&&(b=!0);return value})):""}function m(a,d){var e=b("input[name='"+d+"']").closest(".file_field");!1==a.is_image?e.find(".file_set").show().find(".filename").html('<img src="'+
EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+a.file_name):e.find(".file_set").show().find(".filename").html('<img src="'+a.thumb+'" /><br />'+a.file_name);e.find(".choose_file").hide();e.find(".undo_remove").hide();e.find('input[name*="_hidden_file"]').val(a.file_name);e.find('input[name*="_hidden_dir"], select[name*="_directory"]').val(a.upload_location_id)}function h(a,d){b(a,d).each(function(){var a=b(this).closest(".file_field"),d=a.find(".choose_file"),c=
a.find(".no_file"),f=b(this).data("content-type"),h=b(this).data("directory"),k=[],f={content_type:f,directory:h};b.ee_filebrowser.add_trigger(d,b(this).attr("name"),f,m);fileselector=d.length?d:c;a.find(".remove_file").click(function(){a.find("input[type=hidden]").val(function(a,b){k[a]=b;return""});a.find(".file_set").hide();a.find(".sub_filename a").show();fileselector.show();return!1});a.find(".undo_remove").click(function(){a.find("input[type=hidden]").val(function(a){return k.length?k[a]:""});
a.find(".file_set").show();a.find(".sub_filename a").hide();fileselector.hide();return!1})})}EE.publish.file_browser.textarea=function(){b.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate",function(a){var d,e="",g="",c="",f="";button_id=b(this).parent().attr("class").match(/id(\d+)/);null!=button_id&&(button_id=button_id[1]);textareaId=b(this).closest("#markItUpWrite_mode_textarea").length?"write_mode_textarea":b(this).closest(".publish_field").attr("id").replace("hold_field_","field_id_");
void 0!=textareaId&&(d=b("#"+textareaId),d.focus());a.is_image?(g=EE.upload_directories[a.upload_location_id].properties,c=EE.upload_directories[a.upload_location_id].pre_format,f=EE.upload_directories[a.upload_location_id].post_format,image_tag=null==button_id?EE.filebrowser.image_tag:EE.filebrowser["image_tag_"+button_id],e=image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+a.upload_location_id+"}"+a.file_name+'$2"'),dimensions="","undefined"!=typeof a.file_hw_original&&
""!=a.file_hw_original&&(dimensions=a.file_hw_original.split(" "),dimensions='height="'+dimensions[0]+'" width="'+dimensions[1]+'"'),e=e.replace(/\/?>$/,dimensions+" "+g+" />"),e=c+e+f):(g=EE.upload_directories[a.upload_location_id].file_properties,c=EE.upload_directories[a.upload_location_id].file_pre_format,c+='<a href="{filedir_'+a.upload_location_id+"}"+a.file_name+'" '+g+" >",f="</a>",f+=EE.upload_directories[a.upload_location_id].file_post_format);d.is("textarea")?(d.is(".markItUpEditor")||
(d.markItUp(myNobuttonSettings),d.closest(".markItUpContainer").find(".markItUpHeader").hide(),d.focus()),a.is_image?b.markItUp({replaceWith:e}):b.markItUp({key:"L",name:"Link",openWith:c,closeWith:f,placeHolder:a.file_name})):d.val(function(a,b){b+=c+e+f;return l(b)})})};EE.publish.file_browser.file_field=function(){h("input[type=file]","#publishForm, .pageContents");Grid.bind("file","display",function(a){h("input[type=file]",a)})};EE.publish.file_browser.category_edit_modal=function(){h("input[type=file]",
"#cat_modal_container")};b(function(){EE.filebrowser.publish&&setTimeout(function(){EE.publish.file_browser.file_field();EE.publish.file_browser.textarea()},15)})})(jQuery);
