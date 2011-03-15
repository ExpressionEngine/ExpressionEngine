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

EE.file_manager=EE.file_manager||{};EE.file_manager.sync_files=EE.file_manager.sync_files||{};EE.file_manager.sync_running=0;EE.file_manager.sync_errors=[];EE.file_manager.resize_ids=[];$(document).ready(function(){$.template("sync_complete_template",$("<div />").append($("#sync_complete_template").remove()));EE.file_manager.sync_listen()});
EE.file_manager.sync_listen=function(){$(".tableSubmit input").click(function(a){a.preventDefault();$(this).hide();EE.file_manager.update_progress();EE.file_manager.sync_files=_.toArray(EE.file_manager.sync_files);var c=_.keys(EE.file_manager.sync_sizes)[0];EE.file_manager.update_progress(0);for(a=0;a<2;a++)setTimeout(function(){EE.file_manager.sync(c)},15)})};EE.file_manager.resize_ids=function(){var a=[];$('input[name="toggle[]"]:checked').each(function(){a.push($(this).val())});return a};
EE.file_manager.sync=function(a){if(!(EE.file_manager.sync_files.length<=0)){var c=EE.file_manager.sync_files.splice(0,5);$.ajax({url:EE.BASE+"&C=content_files&M=do_sync_files",type:"POST",dataType:"json",data:{XID:EE.XID,upload_directory_id:a,sizes:EE.file_manager.sync_sizes,files:c,resize_ids:EE.file_manager.resize_ids()},beforeSend:function(){EE.file_manager.sync_running+=1},complete:function(){EE.file_manager.sync_running-=1;EE.file_manager.sync(a);var b=EE.file_manager.sync_file_count;EE.file_manager.update_progress(Math.round((b-
EE.file_manager.sync_files.length)/b*100));EE.file_manager.finish_sync(a)},error:function(b){b=b.responseText;$.isArray(b)||(b=[b]);for(var d=0,e=b.length;d<e;d++)EE.file_manager.sync_errors.push(b[d])}})}};EE.file_manager.get_directory_name=function(a){return $("#sync table:first tr[data-id="+a+"] td:first").text()};
EE.file_manager.finish_sync=function(a){if(EE.file_manager.sync_running==0){$("#progress").hide();a={directory_name:EE.file_manager.get_directory_name(a),files_processed:EE.file_manager.sync_file_count-EE.file_manager.sync_errors.length,errors:EE.file_manager.sync_errors,error_count:EE.file_manager.sync_errors.length};$.tmpl("sync_complete_template",a).attr("id","sync_complete").appendTo($("#sync"));a.error_count==0?$("#sync_complete ul").hide():$("#sync_complete span").hide()}};
EE.file_manager.update_progress=function(a){var c=$("#progress");c.is(":not(:visible)")&&c.show();c.progressbar({value:a})};
