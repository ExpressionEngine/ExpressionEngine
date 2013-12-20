/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

EE.file_manager=EE.file_manager||{};EE.file_manager.sync_files=EE.file_manager.sync_files||{};EE.file_manager.sync_db=0;EE.file_manager.sync_running=0;EE.file_manager.sync_errors=[];EE.file_manager.resize_ids=[];$(document).ready(function(){$.template("sync_complete_template",$("#sync_complete_template"));$("table#dimensions").toggle_all();EE.file_manager.sync_listen()});
EE.file_manager.sync_listen=function(){$(".tableSubmit input").click(function(a){a.preventDefault();$(this).hide();EE.file_manager.update_progress();EE.file_manager.sync_files=_.toArray(EE.file_manager.sync_files);var b=_.keys(EE.file_manager.sync_sizes)[0];EE.file_manager.update_progress(0);setTimeout(function(){EE.file_manager.sync(b)},15)})};EE.file_manager.resize_ids=function(){var a=[];$('input[name="toggle[]"]:checked').each(function(){a.push($(this).val())});return a};
EE.file_manager.sync=function(a){if(0>=EE.file_manager.sync_files.length){if("y"==EE.file_manager.db_sync)return;EE.file_manager.db_sync="y"}var b=EE.file_manager.sync_files.splice(0,5);$.ajax({url:EE.BASE+"&C=content_files&M=do_sync_files",type:"POST",dataType:"json",data:{XID:EE.XID,upload_directory_id:a,sizes:EE.file_manager.sync_sizes,files:b,resize_ids:EE.file_manager.resize_ids(),db_sync:EE.file_manager.db_sync},beforeSend:function(a,b){EE.file_manager.sync_running+=1},complete:function(b,f){EE.file_manager.sync_running-=
1;EE.file_manager.sync(a);var c=EE.file_manager.sync_file_count;EE.file_manager.update_progress(Math.round(100*((c-EE.file_manager.sync_files.length)/c)));EE.file_manager.finish_sync(a)},success:function(a,b,c){if("success"!=a.message_type)if("undefined"!=typeof a.errors)for(var d in a.errors)EE.file_manager.sync_errors.push("<b>"+d+"</b>: "+a.errors[d]);else EE.file_manager.sync_errors.push("<b>Undefined errors</b>")}})};
EE.file_manager.get_directory_name=function(a){return $("#sync table:first tr[data-id="+a+"] td:first").text()};
EE.file_manager.finish_sync=function(a){0==EE.file_manager.sync_running&&($("#progress").hide(),a={directory_name:EE.file_manager.get_directory_name(a),files_processed:EE.file_manager.sync_file_count-EE.file_manager.sync_errors.length,errors:EE.file_manager.sync_errors,error_count:EE.file_manager.sync_errors.length},$.tmpl("sync_complete_template",a).appendTo("#sync"),0==a.error_count?$("#sync_complete ul").hide():$("#sync_complete span").hide())};
EE.file_manager.update_progress=function(a){var b=$("#progress"),e=$("#progress_bar");b.is(":not(:visible)")&&b.show();e.progressbar({value:a})};
