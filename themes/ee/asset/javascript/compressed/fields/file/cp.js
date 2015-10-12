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
"use strict";!function(i){i(document).ready(function(){function e(e){i(".file-field-filepicker",e).FilePicker({callback:function(i,e){var t=e.input_value;
// Close the modal
e.modal.find(".m-close").click(),
// Assign the value {filedir_#}filename.ext
t.val("{filedir_"+i.upload_location_id+"}"+i.file_name),
// Set the thumbnail
e.input_img.attr("src",i.thumb_path),
// Show the figure
t.siblings("figure").show(),
// Hide the upload button
t.siblings("p.solo-btn").hide(),
// Hide the "missing file" error
t.siblings("em").hide()}}),i("li.remove a").click(function(e){var t=i(this).closest("figure");t.hide(),t.siblings("em").hide(),// Hide the "missing file" erorr
t.siblings('input[type="hidden"]').val(""),t.siblings("p.solo-btn").show(),e.preventDefault()})}e(),Grid.bind("file","display",function(t){var n=i(".file-field-filepicker",t),l=i('input[type="hidden"]',t),a=l.attr("name").replace(/[\[\]']+/g,"_");n.attr("data-input-value",l.attr("name")),n.attr("data-input-image",a),i(".file-chosen img",t).attr("id",a),e(t)})})}(jQuery);