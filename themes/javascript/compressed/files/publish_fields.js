/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.4
 * @filesource
 */

$.ee_filebrowser();EE.namespace("EE.publish.file_browser");
(function(d){function m(c){var a=!1;return c?(c=c.toString(),c=c.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(b,c){var a=c.split("|!|");return!0===altKey?void 0!==a[1]?a[1]:a[0]:void 0===a[1]?"":a[0]}),c=c.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(b,c){var d=c.split(":!:");if(!0===a)return!1;value=prompt(d[0],d[1]?d[1]:"");null===value&&(a=!0);return value})):""}function n(c,a){var b=d("input[name='"+a+"']").closest(".file_field");!1==c.is_image?b.find(".file_set").show().find(".filename").html('<img src="'+
EE.PATH_CP_GBL_IMG+'default.png" alt="'+EE.PATH_CP_GBL_IMG+'default.png" /><br />'+c.file_name):b.find(".file_set").show().find(".filename").html('<img src="'+c.thumb+'" /><br />'+c.file_name);b.find(".choose_file").hide();b.find(".undo_remove").hide();b.find('input[name*="_hidden_file"]').val(c.file_name);b.find('input[name*="_hidden_dir"], select[name*="_directory"]').val(c.upload_location_id)}function k(c,a){d(c,a).each(function(){var b=d(this).closest(".file_field"),a=b.find(".choose_file"),c=
b.find(".no_file"),e=d(this).data("content-type"),f=d(this).data("directory"),l=[],e={content_type:e,directory:f};d.ee_filebrowser.add_trigger(a,d(this).attr("name"),e,n);fileselector=a.length?a:c;b.find(".remove_file").click(function(){b.find("input[type=hidden]").val(function(a,b){l[a]=b;return""});b.find(".file_set").hide();b.find(".sub_filename a").show();fileselector.show();return!1});b.find(".undo_remove").click(function(){b.find("input[type=hidden]").val(function(a){return l.length?l[a]:""});
b.find(".file_set").show();b.find(".sub_filename a").hide();fileselector.hide();return!1})})}EE.publish.file_browser.textarea=function(c){d.ee_filebrowser.add_trigger(d(".btn_img a, .file_manipulate",c),function(a){var b,g="",h="",e="",f="";button_id=d(this).parent().attr("class").match(/id(\d+)/);null!=button_id&&(button_id=button_id[1]);void 0!==c?(b=d("textarea",c),b.focus()):(textareaId=d(this).closest("#markItUpWrite_mode_textarea").length?"write_mode_textarea":d(this).closest(".publish_field").attr("id").replace("hold_field_",
"field_id_"),void 0!=textareaId&&(b=d("textarea[name="+textareaId+"]",c),b.focus()));a.is_image?(h=EE.upload_directories[a.upload_location_id].properties,e=EE.upload_directories[a.upload_location_id].pre_format,f=EE.upload_directories[a.upload_location_id].post_format,image_tag=null==button_id?EE.filebrowser.image_tag:EE.filebrowser["image_tag_"+button_id],g=image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/,'src="$1{filedir_'+a.upload_location_id+"}"+a.file_name+'$2"'),dimensions="","undefined"!=
typeof a.file_hw_original&&""!=a.file_hw_original&&(dimensions=a.file_hw_original.split(" "),dimensions='height="'+dimensions[0]+'" width="'+dimensions[1]+'"'),g=g.replace(/\/?>$/,dimensions+" "+h+" />"),g=e+g+f):(h=EE.upload_directories[a.upload_location_id].file_properties,e=EE.upload_directories[a.upload_location_id].file_pre_format,e+='<a href="{filedir_'+a.upload_location_id+"}"+a.file_name+'" '+h+" >",f="</a>",f+=EE.upload_directories[a.upload_location_id].file_post_format);b.is("textarea")?
(b.is(".markItUpEditor")||(b.markItUp(myNobuttonSettings),b.closest(".markItUpContainer").find(".markItUpHeader").hide(),b.focus()),a.is_image?d.markItUp({replaceWith:g}):d.markItUp({key:"L",name:"Link",openWith:e,closeWith:f,placeHolder:a.file_name})):b.val(function(a,b){b+=e+g+f;return m(b)})})};EE.publish.file_browser.file_field=function(){k("input[type=file]","#publishForm .publish_file, .pageContents");Grid.bind("file","display",function(c){k("input[type=file]",c)})};EE.publish.file_browser.category_edit_modal=
function(){k("input[type=file]","#cat_modal_container")};d(function(){EE.filebrowser.publish&&setTimeout(function(){EE.publish.file_browser.file_field();EE.publish.file_browser.textarea()},15)})})(jQuery);
