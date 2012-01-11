/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function(a){function k(){var d=a("#dir_choice");c.dialog({width:968,height:615,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.filebrowser.window_title,autoOpen:!1,zIndex:99999,open:function(){var b=e[g].directory;isNaN(b)||d.val(b);d.trigger("interact");a("#dir_choice").val()},close:function(){a.ee_filebrowser.reload();a("#keywords",c).val("")}});var b;a("#file_browser_body").find("table").each(function(){b=a(this);if(b.data("table_config"))return!1});var h=b.data("table_config");
b.table(h);b.table("add_filter",d);b.table("add_filter",a("#keywords"));var f=b.table("get_template");thumb_template=a("#thumbTmpl").remove().html();table_container=b.table("get_container");thumb_container=a("#file_browser_body");a("#view_type").change(function(){this.value=="thumb"?(b.detach(),b.table("set_container",thumb_container),b.table("set_template",thumb_template),b.table("add_filter",{per_page:36})):(thumb_container.html(b),b.table("set_container",table_container),b.table("set_template",
f),b.table("add_filter",{per_page:15}))});b.bind("tableupdate",function(){a("#view_type").val()=="thumb"&&(a("a.file_browser_thumbnail:nth-child(9n)").addClass("last"),a("a.file_browser_thumbnail:nth-child(9n+1)").addClass("first"),a("a.file_browser_thumbnail:gt(26)").addClass("last_row"))});a("#upload_form",c).submit(a.ee_filebrowser.upload_start);a("#file_browser_body",c).addClass(l)}var c,g="",l="list",j=0,e={},i;a.ee_filebrowser=function(){a.ee_filebrowser.endpoint_request("setup",function(d){dir_files_structure=
{};dir_paths={};c=a(d.manager).appendTo(document.body);for(var b in d.directories)j||(j=b),dir_files_structure[b]="";k();typeof a.ee_fileuploader!="undefined"&&a.ee_fileuploader({type:"filebrowser",open:function(){a.ee_fileuploader.set_directory_id(a("#dir_choice").val())},close:function(){a("#file_uploader").removeClass("upload_step_2").addClass("upload_step_1");a("#file_browser").size()&&a.ee_filebrowser.reload()},trigger:"#file_browser #upload_form input"})})};a.ee_filebrowser.endpoint_request=
function(d,b,c){typeof c=="undefined"&&a.isFunction(b)&&(c=b,b={});b=a.extend(b,{action:d});a.ajax({url:EE.BASE+"&"+EE.filebrowser.endpoint_url,type:"GET",dataType:"json",data:b,cache:!1,success:function(a){typeof c=="function"&&c.call(this,a)}})};a.ee_filebrowser.add_trigger=function(d,b,h,f){f?e[b]=h:a.isFunction(b)?(f=b,b="userfile",e[b]={content_type:"any",directory:"all"}):a.isFunction(h)&&(f=h,e[b]={content_type:"any",directory:"all"});a(d).click(function(){var d=this;g=b;e[g].directory!="all"?
(a("#dir_choice",c).val(e[g].directory),a("#dir_choice_form .dir_choice_container",c).hide()):(a("#dir_choice",c).val(),a("#dir_choice_form .dir_choice_container",c).show());c.dialog("open");i=function(a){f.call(d,a,b)};return!1})};a.ee_filebrowser.get_current_settings=function(){return e[g]};a.ee_filebrowser.placeImage=function(d){a.ee_filebrowser.endpoint_request("file_info",{file_id:d},function(a){i(a);c.dialog("close")});return!1};a.ee_filebrowser.clean_up=function(d){d&&i(d);a("#keywords",c).val("");
c.dialog("close")};a.ee_filebrowser.reload_directory=function(){a.ee_filebrowser.reload()};a.ee_filebrowser.reload=function(){}})(jQuery);
