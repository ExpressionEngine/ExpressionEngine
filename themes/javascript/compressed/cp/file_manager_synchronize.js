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

EE.file_manager=EE.file_manager||{};EE.file_manager.sync_files=EE.file_manager.sync_files||{};EE.file_manager.sync_running=0;EE.file_manager.sync_errors=[];$(document).ready(function(){$.template("sync_complete_template",$("#sync_complete_template").remove());EE.file_manager.sync_listen()});
EE.file_manager.sync_listen=function(){$(".tableSubmit input").click(function(a){a.preventDefault();$(this).hide();EE.file_manager.update_progress();EE.file_manager.sync_files=_.toArray(EE.file_manager.sync_files);var b=_.keys(EE.file_manager.sync_sizes)[0];EE.file_manager.update_progress(0);for(a=0;a<2;a++)setTimeout(function(){EE.file_manager.sync(b)},15)})};
EE.file_manager.sync=function(a){if(!(EE.file_manager.sync_files.length<=0)){var b=EE.file_manager.sync_files.splice(0,5);$.ajax({url:EE.BASE+"&C=content_files&M=do_sync_files",type:"POST",dataType:"json",data:{XID:EE.XID,upload_directory_id:a,sizes:EE.file_manager.sync_sizes,files:b},beforeSend:function(){EE.file_manager.sync_running+=1},complete:function(){EE.file_manager.sync_running-=1;EE.file_manager.sync(a);var c=EE.file_manager.sync_file_count;EE.file_manager.update_progress(Math.round((c-
EE.file_manager.sync_files.length)/c*100));EE.file_manager.finish_sync()},error:function(c,e,d){$.isArray(d)||(d=[d]);c=0;for(e=d.length;c<e;c++)EE.file_manager.sync_errors.push(d[c])}})}};
EE.file_manager.finish_sync=function(){if(EE.file_manager.sync_running==0){$("#progress").hide();var a={files_processed:EE.file_manager.sync_file_count-EE.file_manager.sync_errors.length,errors:EE.file_manager.sync_errors,error_count:EE.file_manager.sync_errors.length};console.log(a);$.tmpl("sync_complete_template",a).appendTo($("#sync"))}};EE.file_manager.update_progress=function(a){var b=$("#progress");b.is(":not(:visible)")&&b.show();b.progressbar({value:a})};
