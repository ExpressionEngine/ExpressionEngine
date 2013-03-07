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

var crop=null,edit_mode=!1,cropCoords,do_crop,crop_coords_array,$image=$("#file_manager_edit_file img"),oversized_class="oversized";cropCoords=function(a){$("#crop_x").val(Math.floor(a.x));$("#crop_y").val(Math.floor(a.y));$("#crop_width").val(Math.floor(a.w));$("#crop_height").val(Math.floor(a.h))};
function clearBoxes(){$("#crop_x").val("");$("#crop_y").val("");$("#crop_width").val(EE.filemanager.image_width);$("#crop_height").val(EE.filemanager.image_height);$("#resize_width").val(EE.filemanager.image_width);$("#resize_height").val(EE.filemanager.image_height)}
$(document).ready(function(){$("#cancel_crop").click(function(){void 0!==crop&&null!==crop&&(crop.destroy(),crop=null);cropCoords({h:EE.filemanager.image_height,w:EE.filemanager.image_width,x:"",y:""});$("#toggle_crop").show();$("#cancel_crop").hide();return!1});$("#toggle_crop").click(function(){void 0===crop_coords_array&&(crop_coords_array=[50,50,100,100]);$("#toggle_crop").hide();$("#cancel_crop").show();crop=$.Jcrop("#file_manager_edit_file img",{setSelect:crop_coords_array,onChange:cropCoords,
onSelect:function(){edit_mode=!0}});return!1});$(".crop_dim").keyup(function(){$("#toggle_crop").hide();$("#cancel_crop").show()});$("form#image_resize_form").resize_scale({submit_resize:"#submit_resize",cancel_resize:"#cancel_resize",default_height:EE.filemanager.image_height,default_width:EE.filemanager.image_width,resize_confirm:EE.filemanager.resize_over_confirmation})});
