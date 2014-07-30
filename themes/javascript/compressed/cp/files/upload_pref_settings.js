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
$(document).ready(function(){$(".remove_size").click(function(){var e=$(this).attr("size_short_name_").substr(16),t=$(this).parent().parent();return alert(e),$.ajax({dataType:"json",data:"id="+e,url:EE.BASE+"&C=content_files&M=delete_dimension",success:function(e){"success"===e.response?($.ee_notice(EE.lang.size_deleted,{type:"success",open:!0,close_on_click:!0}),$(t).fadeOut("slow",function(){$(this).remove()})):$.ee_notice(EE.lang.size_not_deleted,{type:"error",open:!0,close_on_click:!0})}}),!1})});