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

$("synchronize").click(function(a){a.preventDefault();$(this).hide();EE.file_manager.update_progress();EE.file_manager.sync_files.files=jsFiles;EE.file_manager.sync_files.file_count=jsFiles.length;var b=$("#upload_directory_id");for(a=0;a<2;a++)setTimeout(function(){EE.file_manager.sync_files(b)},15)});EE.file_manager.sync_files.running=0;EE.file_manager.sync_files.errors=[];
EE.file_manager.sync_files=function(a){if(EE.file_manager.sync_files.files.length<=0)return EE.filemanager.finish_sync();var b=EE.file_manager.sync_files.files.splice(0,5);$.ajax({url:EE.BASE+"&C=content_files&M=sync_directory",type:"POST",dataType:"json",data:{XID:EE.XID,upload_directory:upload_directory,files:b},beforeSend:function(){EE.file_manager.sync_files.running+=1},complete:function(){EE.file_manager.sync_files.running-=1;EE.file_manager.sync_files(a);var c=EE.file_manager.sync_files.file_count;
EE.file_manager.update_progress(Math.round((c-EE.file_manager.sync_files.files.length)/c))},success:function(){},error:function(c,e,d){$.isArray(d)||(d=[d]);c=0;for(e=d.length;c<e;c++)EE.file_manager.sync_files.errors.push(d[c])}})};
EE.file_manager.finish_sync=function(){if(EE.file_manager.sync_files.running==0){$(".progress").hide();$.tmpl("sync_complete_template",{files_processed:EE.file_manager.sync_files.file_count-EE.file_manager.sync_files.errors.length,errors:EE.file_manager.sync_files.errors,error_count:EE.file_manager.sync_files.errors.length}).appendTo()}};EE.file_manager.update_progress=function(a){var b=$("#progress");b.is(":not(:visible)")&&b.show();b.progressbar({value:a})};
