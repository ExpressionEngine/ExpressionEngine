/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
EE.file_manager=EE.file_manager||{},EE.file_manager.sync_files=EE.file_manager.sync_files||{},EE.file_manager.sync_db=0,EE.file_manager.sync_running=0,EE.file_manager.sync_errors=[],EE.file_manager.resize_ids=[],EE.file_manager.sync_timeout_id=0,$(document).ready(function(){EE.file_manager.sync_listen()}),EE.file_manager.sync_listen=function(){$("form.settings input.btn").click(function(e){e.preventDefault(),
// Get array of files
EE.file_manager.sync_files=_.toArray(EE.file_manager.sync_files);
// Get upload directory
var n=_.keys(EE.file_manager.sync_sizes)[0];EE.file_manager.update_progress(0),
// Disable sync button
$("input.btn",this).prop("disabled",!0),
// Send ajax requests
// Note- testing didn't show async made much improvement on time
EE.file_manager.sync_timeout_id=setTimeout(function(){EE.file_manager.sync(n)},15)})},EE.file_manager.resize_ids=function(){var e=[];return $('input[name="sizes[]"]:checked').each(function(){e.push($(this).val())}),e},/**
 * Fire off the Ajax request, which then listens for the finish and then fires off the next Ajax request and so on
 *
 * @param {Number} upload_directory_id The id of the upload directory to pass to the controller method
 */
EE.file_manager.sync=function(e){
// If no files are left, check if db sync has run- if so, get outta here
if(EE.file_manager.sync_files.length<=0){if("y"==EE.file_manager.db_sync)return void clearTimeout(EE.file_manager.sync_timeout_id);EE.file_manager.db_sync="y"}
// There should only be one place we're splicing the files array and THIS is it
var n=EE.file_manager.sync_files.splice(0,5);$.ajax({url:EE.file_manager.sync_endpoint,type:"POST",dataType:"json",data:{XID:EE.XID,upload_directory_id:e,sizes:EE.file_manager.sync_sizes,files:n,resize_ids:EE.file_manager.resize_ids(),db_sync:EE.file_manager.db_sync,errors:EE.file_manager.sync_errors},beforeSend:function(e,n){
// Increment the running count
EE.file_manager.sync_running+=1},complete:function(n,i){
// Decrement the running count
EE.file_manager.sync_running-=1;
// Update the progress bar
var r=EE.file_manager.sync_file_count,a=EE.file_manager.sync_files.length,s=r-a;EE.file_manager.update_progress(Math.round(s/r*100)),
// Fire off another Ajax request
EE.file_manager.sync(e),EE.file_manager.finish_sync(e)},success:function(e,n,i){if("success"!=e.message_type)if("undefined"!=typeof e.errors)for(var r in e.errors)EE.file_manager.sync_errors.push("<b>"+r+"</b>: "+e.errors[r]);else EE.file_manager.sync_errors.push("<b>Undefined errors</b>"),d}})},/**
 * Show the sync complete summary
 *
 * This should contain the number of files processed, the number of errors and the errors themselves
 */
EE.file_manager.finish_sync=function(e){if(0==EE.file_manager.sync_running)if(0==EE.file_manager.sync_errors.length)
// No errors? Success flashdata message should be set,
// redirect back to the sync page to show success message
window.location=EE.file_manager.sync_baseurl;else{
// If there are errors, pass them through POST, there may be too
// many to store in a flashdata cookie
var n=$("<input>",{type:"hidden",name:"errors",value:JSON.stringify(EE.file_manager.sync_errors)});$(".w-12 form.settings").append(n).submit()}},/**
 * Update the progress bar
 *
 * @param {Number} progress_percentage The percentage of progress, represented as an integer (e.g. 56 = 56%)
 */
EE.file_manager.update_progress=function(e){var n=$(".progress-bar"),i=$(".progress",n);n.is(":not(:visible)")&&n.show(),i.css("width",e+"%")};