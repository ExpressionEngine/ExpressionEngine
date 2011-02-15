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

EE.file_manager=EE.file_manager||{};EE.file_manager.sync_files=EE.file_manager.sync_files||{};$(".tableSubmit input").click(function(a){a.preventDefault();$(this).hide();EE.file_manager.update_progress();EE.file_manager.sync_files=_.toArray(EE.file_manager.sync_files);var c=_.keys(EE.file_manager.sync_sizes)[0];EE.file_manager.update_progress(0);for(a=0;a<2;a++)setTimeout(function(){EE.file_manager.sync(c)},15)});EE.file_manager.sync_running=0;EE.file_manager.sync_errors=[];
EE.file_manager.sync=function(a){if(EE.file_manager.sync_files.length<=0)return EE.file_manager.finish_sync();var c=EE.file_manager.sync_files.splice(0,5);$.ajax({url:EE.BASE+"&C=content_files&M=do_sync_files",type:"POST",dataType:"json",data:{XID:EE.XID,upload_directory_id:a,sizes:{"1":{stuff:"yay"}},files:c},beforeSend:function(){console.log("start");EE.file_manager.sync_running+=1},complete:function(){console.log("finish");EE.file_manager.sync_running-=1;EE.file_manager.sync(a);var b=EE.file_manager.sync_file_count;
EE.file_manager.update_progress(Math.round((b-EE.file_manager.sync_files.length)/b))},success:function(b){console.log(b)},error:function(b,e,d){console.log("Error: "+d);$.isArray(d)||(d=[d]);b=0;for(e=d.length;b<e;b++)EE.file_manager.sync_errors.push(d[b])}})};
EE.file_manager.finish_sync=function(){if(EE.file_manager.sync_running==0){$(".progress").hide();$.tmpl("sync_complete_template",{files_processed:EE.file_manager.sync_file_count-EE.file_manager.sync_errors.length,errors:EE.file_manager.sync_errors,error_count:EE.file_manager.sync_errors.length}).appendTo()}};EE.file_manager.update_progress=function(a){var c=$("#progress");c.is(":not(:visible)")&&c.show();c.progressbar({value:a})};
