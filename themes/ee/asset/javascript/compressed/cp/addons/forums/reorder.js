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
!function(e){"use strict";e(document).ready(function(){e("table").eeTableReorder({afterSort:function(r){e.ajax({url:EE.forums.reorder_url,data:{order:e('input[name="order[]"]').serialize()},type:"POST",dataType:"json",error:function(r,a,o){
// Let the user know something went wrong
0==e("body > .banner").size()&&e("body").prepend(EE.alert.reorder_ajax_fail)}})}}),e(".tbl-ctrls").sortable({axis:"y",// Only allow vertical dragging
containment:"parent",// Contain to parent
handle:"th.reorder-col",// Set drag handle
items:"table",// Only allow these to be sortable
sort:EE.sortable_sort_helper,// Custom sort handler
forcePlaceholderSize:!0,// Custom sort handler
update:function(r,a){e.ajax({url:EE.forums.reorder_url,data:{order:e('input[name="cat_order[]"]').serialize()},type:"POST",dataType:"json",error:function(r,a,o){
// Let the user know something went wrong
0==e("body > .banner").size()&&e("body").prepend(EE.alert.reorder_ajax_fail)}})}})})}(jQuery);