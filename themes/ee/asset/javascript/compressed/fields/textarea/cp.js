/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
"use strict";!function(i){i(document).ready(function(){i(".textarea-field-filepicker").FilePicker({callback:function(i,e){var n=e.input_value;
// Close the modal
e.modal.find(".m-close").click();
// Assign the value {filedir_#}filename.ext
var l='<img src="{filedir_'+i.upload_location_id+"}"+i.file_name+'"';l+=' alt=""',i.file_hw_original&&(dimensions=i.file_hw_original.split(" "),l=l+' height="'+dimensions[0]+'" width="'+dimensions[1]+'"'),l+=">",n.insertAtCursor(l)}})})}(jQuery);