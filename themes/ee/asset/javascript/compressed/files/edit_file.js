/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.4
 * @filesource
 */
// Make sure we can create these methods without issues
var EE=EE||{};EE.publish=EE.publish||{},EE.publish.edit_file=EE.publish.edit_file||{},function(e){
// Reset to default visible tabs
EE.publish.edit_file.reset_tabs=function(){
// Setup default visible tabs
e(".panel-menu li").removeClass("current").filter(":first").addClass("current"),e(".panels > div").removeClass("current").filter(":first").addClass("current")},
// Changes tabs in the modal
EE.publish.edit_file.change_tabs=function(){EE.publish.edit_file.reset_tabs(),
// Show the clicked tab
e(".panel-menu li a").click(function(i){var t=e(this).data("panel");
// Change classes and hide old panels
e(".panels").children().hide().removeClass("current").filter("#"+t).show().addClass("current"),
// Change classes on tabs
e(this).parent().addClass("current").siblings().removeClass("current"),i.preventDefault()})},EE.publish.edit_file.change_tabs(),
// Hides and shows image tools, so only one tool is showing at a time
EE.publish.edit_file.image_tool_select=function(){e("#image_tools input[name=image_tool]").click(function(i){
// Hide existing exposed image tools
e(this).parent().parent().siblings().find("div").slideUp(),
// Show image tool
e(this).parent().siblings("div").slideDown(),
// Reset resize
"resize"!=e(this).val()&&(e("#resize_height").val(EE.filemanager.image_height),e("#resize_width").val(EE.filemanager.image_width)),
// Change the value of action hidden input
e("input[name=action]").val(e(this).val())})},EE.publish.edit_file.image_tool_select(),
// Submit listener doesn't work here, I assume due to iframe
e("form#edit_file_metadata").resize_scale({cancel_resize:"#cancel_resize",default_height:EE.filemanager.image_height,default_width:EE.filemanager.image_width})}(jQuery);