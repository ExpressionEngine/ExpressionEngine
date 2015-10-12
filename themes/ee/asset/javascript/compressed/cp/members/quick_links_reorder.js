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
!function(e){"use strict";e(document).ready(function(){e("table").eeTableReorder({afterSort:function(r){e.ajax({url:EE.quick_links.reorder_url,data:{order:e('input[name="order[]"]').serialize()},type:"POST",dataType:"json",success:function(r){e("table tbody").empty().append(e(r.success).find("tr:has(td)"))},error:function(r,t,a){
// Let the user know something went wrong
0==e("body > .banner").size()&&e("body").prepend(EE.alert.reorder_ajax_fail)}})}})})}(jQuery);