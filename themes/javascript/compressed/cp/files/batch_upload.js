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

$(document).ready(function(){$("select[name=upload_dirs]").change(function(){var b=$(this).val();$.ajax({url:EE.BASE+"&C=content_files&M=get_dir_cats",type:"POST",data:{XID:EE.XID,upload_directory_id:b},success:function(a){!0===a.error?$("#file_cats").html(""):(a='<fieldset class="holder">'+a+"</fieldset>",$("#file_cats").html(a),$("#file_cats").find(".edit_categories_link").hide())},error:function(a){$("#file_cats").html("")}})})});
