/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
"use strict";!function(i){EE.cp.categoryEdit={init:function(e){var e=e||i("body"),t=i("input[name=cat_image_select]",e).parent(),a=i("input[name=cat_image]",e),n=a.parents("figure");""==a.attr("value")?n.hide():t.hide(),i("input[value=choose], li.edit a",e).addClass("m-link").attr("rel","modal-file").attr("href",EE.category_edit.filepicker_url).FilePicker({callback:function(e,l){
// Close the modal
l.modal.find(".m-close").click(),
// Assign the value {filedir_#}filename.ext
a.val("{filedir_"+e.upload_location_id+"}"+e.file_name),
// Set the thumbnail
i("img",n).attr("src",e.path),
// Show the figure
a.parents("figure").show(),
// Hide the upload button
t.hide(),
// Hide the "missing file" error
a.siblings("em").hide()}}),i("li.remove a",e).click(function(a){var n=i(this).parents("figure");n.hide(),n.siblings("em").hide(),// Hide the "missing file" erorr
n.find('input[type="hidden"]').val(""),a.preventDefault(),
// Return radio selection back to none
i("input[value=none]",e).click(),t.show()})}}}(jQuery);