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
!function(e){
// --------------------------------------------------------------------
/*
	 * Sets up all filebrowser events
	 */
function t(){var t=e("#dir_choice"),r=.95*e(window).width();r>974&&(r=974),
// Set up modal dialog
i.dialog({width:r,height:615,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.filebrowser.window_title,autoOpen:!1,zIndex:99999,open:function(r,i){var o=c[n].directory;isNaN(o)||t.val(o),
// force a trigger check
t.trigger("interact");e("#dir_choice").val()},close:function(t,r){
// Clear out keyword filter
e("#keywords",i).val("")}});var o=e("#file_browser_body").find("table");o.each(function(){return _=e(this),_.data("table_config")?!1:void 0});var l=_.data("table_config");_.table(l),
// Set directory in case filter happens before input has changed (because the
// filter is only set on certain interaction events)
// $table.table('add_filter', { 'dir_choice': $dir_choice.val() });
_.table("add_filter",t),_.table("add_filter",e("#keywords"));var d=_.table("get_template");thumb_template=e("#thumbTmpl").remove().html(),table_container=_.table("get_container"),thumb_container=e("#file_browser_body"),//$('div').insertBefore($table);
e("#view_type").change(function(){"thumb"==this.value?(_.detach(),_.table("set_container",thumb_container),_.table("set_template",thumb_template),_.table("add_filter",{per_page:36})):(thumb_container.html(_),_.table("set_container",table_container),_.table("set_template",d),_.table("add_filter",{per_page:15}))}),
// Bind the upload submit event
e("#upload_form",i).submit(e.ee_filebrowser.upload_start),
// Add the display type as a class to file_browser_body
e("#file_browser_body",i).addClass(a)}
// --------------------------------------------------------------------
/**
	 * Hides the directory switcher based on settings passed to add_trigger
	 */
function r(){"all"!=c[n].directory?(e("#dir_choice",i).val(c[n].directory),e("#dir_choice_form .dir_choice_container",i).hide()):(e("#dir_choice",i).val(),e("#dir_choice_form .dir_choice_container",i).show())}var i,o,n="",a="list",l=0,c={},d="",_=null;/*
	 * Sets up the filebrowser - call this before anything else
	 *
	 * @todo make callbacks overridable ($.extend)
	 */
e.ee_filebrowser=function(){
// Setup!
e.ee_filebrowser.endpoint_request("setup",function(r){dir_files_structure={},dir_paths={},i=e(r.manager).appendTo(document.body);for(var o in r.directories)l||(l=o),dir_files_structure[o]="";t(),
// Load the file uploader
"undefined"!=typeof e.ee_fileuploader&&e.ee_fileuploader({type:"filebrowser",open:function(t){e.ee_fileuploader.set_directory_id(e("#dir_choice").val())},close:function(t){
// Make sure the button bar is showing the correct items
e("#file_uploader").removeClass("upload_step_2").addClass("upload_step_1"),e("#file_browser").size()&&
// Reload the contents for the current directory
e.ee_filebrowser.reload()},trigger:"#file_browser #upload_form input"})})},
// --------------------------------------------------------------------
/*
	 * Generic function to make requests to the backend. Everything! is handled by the backend.
	 *
	 * Currently supported types:
	 *		setup				 - called automatically | returns manager html and all directories
	 *		diretory			 - returns directory name
	 *		directories			 - returns all directories
	 *		directory_contents	 - returns directory information and files ({url: '', id: '', files: {...}})
	 */
e.ee_filebrowser.endpoint_request=function(t,r,i){"undefined"==typeof i&&e.isFunction(r)&&(i=r,r={}),r=e.extend(r,{action:t}),e.ajax({url:EE.BASE+"&"+EE.filebrowser.endpoint_url,type:"GET",dataType:"json",data:r,cache:!1,success:function(e,t,r){return e.error?void(d=e.error):void("function"==typeof i&&i.call(this,e))}})},
// --------------------------------------------------------------------
/*
	 * Allows you to bind elements that will open the file browser
	 * The callback is called with the file information when a file
	 * is chosen.
	 *
	 * @param {String} el The jQuery Object or selector
	 * @param {String} field_name The name of the field you're adding a trigger to
	 * @param {Object} new_settings The settings for this specific field,
	 *		the only settings used are content_type and directory. content_type
	 *		can be set to 'any' or 'image'. Directory can be set to 'all' or
	 *		a specific directory ID
	 */
e.ee_filebrowser.add_trigger=function(t,a,l,_){_?c[a]=l:e.isFunction(a)?(_=a,a="userfile",c[a]={content_type:"any",directory:"all"}):e.isFunction(l)&&(_=l,c[a]={content_type:"any",directory:"all"}),e(t).click(function(){
// Check to see if we have any errors from setup
if(d)return alert(d),!1;var e=this;
// Change the upload field to their preferred name
// Restrict the upload directory options to the specified directory
return n=a,r(),i.dialog("open"),o=function(t){_.call(e,t,a)},!1})},
// --------------------------------------------------------------------
/**
	 * Gets the settings of the currently selected field
	 *
	 * @returns An object containing the settings passed in for the current field
	 * @type Object
	 */
e.ee_filebrowser.get_current_settings=function(){return c[n]},
// --------------------------------------------------------------------
/*
	 * Place Image
	 *
	 * Convenience method that gets bound as an inline click event. Yes,
	 * inline click event - eat me.
	 */
e.ee_filebrowser.placeImage=function(t){return e.ee_filebrowser.endpoint_request("file_info",{file_id:t},function(e){o(e),i.dialog("close")}),!1},
// --------------------------------------------------------------------
/**
	 * Clear caches and close the file browser
	 */
e.ee_filebrowser.clean_up=function(t){void 0!=i&&(t&&o(t),
// Clear out keyword filter
e("#keywords",i).val(""),i.dialog("close"))},
// --------------------------------------------------------------------
/**
	 * Refreshes the file browser with the newly upload files
	 *
	 * @param {Number} directory_id The directory ID to refresh
	 * @deprecated since 2.4, use reload()
	 */
e.ee_filebrowser.reload_directory=function(t){e.ee_filebrowser.reload()},
// --------------------------------------------------------------------
/**
	 * Refreshes the file browser with the newly upload files
	 */
e.ee_filebrowser.reload=function(){_&&(_.table("clear_cache"),_.table("refresh"))}}(jQuery);