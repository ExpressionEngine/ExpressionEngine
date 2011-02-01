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

var crop=null,edit_mode=false;cropCoords=function(a){$("#crop_x").val(Math.floor(a.x));$("#crop_y").val(Math.floor(a.y));$("#crop_width").val(Math.floor(a.w));$("#crop_height").val(Math.floor(a.h))};function clearBoxes(){$("#crop_x").val("");$("#crop_y").val("");$("#crop_width").val(EE.filemanager.image_width);$("#crop_height").val(EE.filemanager.image_height);$("#resize_width").val(EE.filemanager.image_width);$("#resize_height").val(EE.filemanager.image_height)}
$(document).ready(function(){$("#file_manager_toolbar").accordion({autoHeight:false,header:"h3"});$("#cancel_crop").click(function(){if(crop!==undefined){crop.destroy();crop=null;cropCoords({h:EE.filemanager.image_height,w:EE.filemanager.image_width,x:"",y:""});$("#toggle_crop").parent("li").show();$("#cancel_crop").parent("li").hide();$("#image_edit_form input[name=action]").val("")}return false});$("#toggle_crop").click(function(){if(crop_coords_array===undefined)crop_coords_array=[50,50,100,100];
$("#toggle_crop").parent("li").hide();$("#cancel_crop").parent("li").show();$("#image_edit_form input[name=action]").val("crop");crop=$.Jcrop("#file_manager_edit_file img",{setSelect:crop_coords_array,onChange:cropCoords,onSelect:function(){edit_mode=true}});return false});$(".crop_dim").keyup(function(){})});
