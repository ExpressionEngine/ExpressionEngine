/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
!function(e){var i,t,a,l,o=!0;/**
	 * Loads in the html needed and fires off the function to build the dialog
	 *
	 * Options you can pass in:
	 *	- type:			string		either 'filebrowser' or 'filemanager', this is
	 *								used to determine what buttons to show
	 *	- trigger:		string		the jQuery selector to bind the upload dialog to
	 *	- load:			function	callback called when the modal is loaded
	 *	- open:			function	callback called when opening the modal
	 *	- after_upload:	function	callback called after the upload is complete
	 *	- close:		function	callback called when closing the modal
	 */
e.ee_fileuploader=function(t){var l={};a=e.extend({},l,t),e.ee_filebrowser.endpoint_request("setup_upload",function(t){i=e(t.uploader),e(document.body).append(i),_EE_uploader_attached()})},e.ee_fileuploader.setSource=function(t,l){i.find(t).attr("src",l),i=i.first(),i.removeClass().addClass("before_upload"),"filemanager"==a.type?i.find(".button_bar .filebrowser").remove():"filebrowser"==a.type&&i.find(".button_bar .filemanager").remove(),e(document).ready(function(){e.ee_fileuploader.build_dialog()}),"function"==typeof a.load&&a.load.call(this,i)},
// --------------------------------------------------------------------
/**
	 * Builds the jQuery UI dialog, adds two listeners to the dialog, and adds
	 * a listener to the upload button on the file chooser
	 */
e.ee_fileuploader.build_dialog=function(){i.dialog({width:600,height:370,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.fileuploader.window_title,autoOpen:!1,zIndex:99999,open:function(){
// Make sure we're on before_upload
f("before_upload"),l={},e("#file_uploader .button_bar .loading").addClass("visualEscapism"),e.ee_fileuploader.reset_upload(),void 0===t&&(t=i.html()),"function"==typeof a.open&&a.open.call(this,i),r()},close:function(){"undefined"!=typeof window.upload_iframe.file&&(o&&
// Delete the file
e.ajax({url:EE.BASE+"&"+EE.fileuploader.delete_url,type:"POST",dataType:"json",data:{file:l.file_id,XID:EE.XID},error:function(e,i,t){console.log(i)}}),
// Call close callback, passing the file info
"function"==typeof a.close&&a.close.call(this,i,l)),i.html(t)}}),
// Bind the open event to the specified trigger
e(document).on("click",a.trigger,function(e){e.preventDefault(),i.dialog("open")})};
// --------------------------------------------------------------------
/**
	 * Listen for clicks on the button_bar's upload file button
	 */
var r=function(){e("#file_uploader .button_bar #rename_file").click(function(i){i.preventDefault(),e("#file_uploader iframe").contents().find("form").trigger("submit")}),e("#file_uploader .button_bar .cancel").live("click",function(t){t.preventDefault(),$iframe=e("#file_uploader iframe").contents(),
// If we're editing file metadata, clear out content
$iframe.find("#edit_file_metadata").size()?(
// Change both resize dimensions back to default
$iframe.find("#resize input").each(function(i){e(this).val(e(this).data("default")).removeClass("oversized")}),
// Clear the radio buttons
$iframe.find("#rotate input").prop("checked",!1)):i.dialog("close")})};
// --------------------------------------------------------------------
/**
	 * Disable the upload by changing the button bar
	 *
	 * @param {Boolean} disable Whether or not to disable the button/upload
	 */
e.ee_fileuploader.reset_upload=function(i){"undefined"==typeof i&&(i=!0),
// Hide loading indicator
e("#file_uploader .button_bar .loading").addClass("visualEscapism"),
// Disable the upload file button
i===!0&&e("#file_uploader .button_bar #upload_file").addClass("disabled-btn").removeClass("submit").unbind()},
// --------------------------------------------------------------------
/**
	 * Fired by the index of the upload after the file field has been
	 * filled out
	 */
e.ee_fileuploader.enable_upload=function(){e("#file_uploader .button_bar #upload_file").addClass("submit").removeClass("disabled-btn").click(function(i){i.preventDefault(),e("#file_uploader .button_bar .loading").removeClass("visualEscapism"),e("#file_uploader iframe").contents().find("form").trigger("submit")})};
// --------------------------------------------------------------------
/**
	 * Cleans up the file upload and the file chooser after a file has
	 * been selected
	 *
	 * @param {Object} file File object passed from
	 */
var n=function(){
// Hide the dialog
i.dialog("close"),
// Close filebrowser
e.ee_filebrowser.clean_up(l)};
// --------------------------------------------------------------------
/**
	 * Sets the directory ID of the iframe
	 *
	 * @param {Number} directory_id The directory ID
	 * @returns Directory ID if it's a valid directory ID, false otherwise
	 * @type Number|Boolean
	 */
e.ee_fileuploader.set_directory_id=function(t){
// Is this a number?
if(!isNaN(parseInt(t,10))){var a=i.find("iframe").attr("src"),l=a.search("&directory_id="),o=e.ee_filebrowser.get_current_settings();
// Check to see if the source already has directory_id and remove it
// Add restrict_directory get variable if we need to restrict to a directory
// Add restrict_image get variable if we need to restrict to images
return l>0&&(a=a.substring(0,l)),a=a+"&directory_id="+t,e(".dir_choice_container:visible").size()<=0&&(a+="&restrict_directory=true"),o&&"image"==o.content_type&&(a+="&restrict_image=true"),i.find("iframe").attr("src",a),t}return!1},
// --------------------------------------------------------------------
/**
	 * This method is called if the file already exists, comes before upload
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
e.ee_fileuploader.file_exists=function(i){e.ee_fileuploader.update_file(i),f("file_exists")},
// --------------------------------------------------------------------
/**
	 * This method is called after the upload
	 *
	 * Responsibilities
	 *	1. Call after_upload callback
	 *	2. Change the class to after_upload
	 *	3. Establish listeners for the buttons
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
e.ee_fileuploader.after_upload=function(t){if(e.ee_fileuploader.update_file(t),o=!1,"function"==typeof a.after_upload&&a.after_upload.call(this,i,l),f("after_upload"),e("#edit_image").toggle(t.is_image),"filemanager"==a.type){
// Create listener for the browse_files button
e("#file_uploader .button_bar").on("click","#browse_files",function(e){n(),e.preventDefault()});for(var r=["edit_file","edit_image"],d=0,u=r.length;u>d;d++){var _=e(".mainTable tr.new:first td:has(img) a[href*="+r[d]+"]").attr("href");e("#"+r[d],"#file_uploader .button_bar").attr("href",_)}}else"filebrowser"==a.type&&(
// Create listener for the choose_file button
e("#file_uploader .button_bar").on("click","#choose_file",function(e){n(),e.preventDefault()}),
// Create listener for edit file button
e("#file_uploader .button_bar").on("click","#edit_file_modal",function(i){e("#file_uploader iframe").contents().find("form#edit_file").trigger("submit"),f("edit_modal"),i.preventDefault()}),
// Create listener for the save file button (independent of choose file)
e("#file_uploader .button_bar").on("click","#save_file",function(i){e("#file_uploader iframe").contents().find("form#edit_file_metadata").trigger("submit"),i.preventDefault()}))},
// --------------------------------------------------------------------
/**
	 * Helper method to change the current file since we can't rely on
	 * window.iframe.variable to always get the latest variable...
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
e.ee_fileuploader.update_file=function(e){l=e};
// --------------------------------------------------------------------
/**
	 * Helper method to change the class of the modal
	 *
	 * @param {String} class_name Name of the class that should be on the modal
	 */
var f=function(i){e("#file_uploader").removeClass("before_upload after_upload file_exists edit_modal").addClass(i)}}(jQuery);