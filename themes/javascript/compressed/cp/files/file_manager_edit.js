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

var crop=null,edit_mode=!1,cropCoords,do_crop,crop_coords_array,$image=$("#file_manager_edit_file img"),oversized_class="oversized";cropCoords=function(a){$("#crop_x").val(Math.floor(a.x));$("#crop_y").val(Math.floor(a.y));$("#crop_width").val(Math.floor(a.w));$("#crop_height").val(Math.floor(a.h))};
function clearBoxes(){$("#crop_x").val("");$("#crop_y").val("");$("#crop_width").val(EE.filemanager.image_width);$("#crop_height").val(EE.filemanager.image_height);$("#resize_width").val(EE.filemanager.image_width);$("#resize_height").val(EE.filemanager.image_height)}
$(document).ready(function(){$("#cancel_crop").click(function(){crop!==void 0&&crop!==null&&(crop.destroy(),crop=null);cropCoords({h:EE.filemanager.image_height,w:EE.filemanager.image_width,x:"",y:""});$("#toggle_crop").show();$("#cancel_crop").hide();return!1});$("#toggle_crop").click(function(){crop_coords_array===void 0&&(crop_coords_array=[50,50,100,100]);$("#toggle_crop").hide();$("#cancel_crop").show();crop=$.Jcrop("#file_manager_edit_file img",{setSelect:crop_coords_array,onChange:cropCoords,
onSelect:function(){edit_mode=!0}});return!1});$(".crop_dim").keyup(function(){$("#toggle_crop").hide();$("#cancel_crop").show()});EE.filemanager.resize_listener()});
EE.filemanager.resize_listener=function(){var a=$("#resize_width"),b=$("#resize_height"),f=$("#submit_resize"),d=$("#cancel_resize");a.add(b).keyup(function(){d.show();var c=$(this),e=c.attr("id");(e==="resize_height"?a:b).val(Math.round((e==="resize_width"?EE.filemanager.image_height/EE.filemanager.image_width:EE.filemanager.image_width/EE.filemanager.image_height)*c.val()));a.val()>EE.filemanager.image_width?a.addClass(oversized_class):a.removeClass(oversized_class);b.val()>EE.filemanager.image_height?
b.addClass(oversized_class):b.removeClass(oversized_class);$image.attr({width:a.val(),height:b.val()})});f.click(function(a){$("."+oversized_class).size()&&confirm(EE.filemanager.resize_over_confirmation)==!1&&a.preventDefault()});d.click(function(c){c.preventDefault();a.val(EE.filemanager.image_width).removeClass(oversized_class);b.val(EE.filemanager.image_height).removeClass(oversized_class);$image.attr({width:EE.filemanager.image_width,height:EE.filemanager.image_height});d.hide()})};
