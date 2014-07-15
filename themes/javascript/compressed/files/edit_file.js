/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.4
 * @filesource
 */
var EE=EE||{};EE.publish=EE.publish||{},EE.publish.edit_file=EE.publish.edit_file||{},function(e){EE.publish.edit_file.reset_tabs=function(){e(".panel-menu li").removeClass("current").filter(":first").addClass("current"),e(".panels > div").removeClass("current").filter(":first").addClass("current")},EE.publish.edit_file.change_tabs=function(){EE.publish.edit_file.reset_tabs(),e(".panel-menu li a").click(function(i){var t=e(this).data("panel");e(".panels").children().hide().removeClass("current").filter("#"+t).show().addClass("current"),e(this).parent().addClass("current").siblings().removeClass("current"),i.preventDefault()})},EE.publish.edit_file.change_tabs(),EE.publish.edit_file.image_tool_select=function(){e("#image_tools input[name=image_tool]").click(function(){e(this).parent().parent().siblings().find("div").slideUp(),e(this).parent().siblings("div").slideDown(),"resize"!=e(this).val()&&(e("#resize_height").val(EE.filemanager.image_height),e("#resize_width").val(EE.filemanager.image_width)),e("input[name=action]").val(e(this).val())})},EE.publish.edit_file.image_tool_select(),e("form#edit_file_metadata").resize_scale({cancel_resize:"#cancel_resize",default_height:EE.filemanager.image_height,default_width:EE.filemanager.image_width})}(jQuery);