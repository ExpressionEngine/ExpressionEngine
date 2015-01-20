/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
$(document).ready(function(){$("select[name=upload_dirs]").change(function(){var e=$(this).val();$.ajax({url:EE.BASE+"&C=content_files&M=get_dir_cats",type:"POST",data:{XID:EE.XID,upload_directory_id:e},success:function(e){if(e.error===!0)return void $("#file_cats").html("");var t='<fieldset class="holder">'+e+"</fieldset>";$("#file_cats").html(t),$("#file_cats").find(".edit_categories_link").hide()},error:function(){$("#file_cats").html("")}})})});