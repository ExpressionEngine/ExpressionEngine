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
!function(e){"use strict";e(document).ready(function(){e(".nestable").nestable({listNodeName:"ul",listClass:"tbl-list",itemClass:"tbl-list-item",rootClass:"nestable",dragClass:"drag-tbl-row",handleClass:"reorder",placeElement:e('<li><div class="tbl-row drag-placeholder"><div class="none"></div></div></li>'),expandBtnHTML:"",collapseBtnHTML:"",maxDepth:10}).on("change",function(){e.ajax({url:EE.cat.reorder_url,data:{order:e(".nestable").nestable("serialize")},type:"POST",dataType:"json",error:function(t,l,i){
// Let the user know something went wrong
0==e("body > .banner").size()&&e("body").prepend(EE.alert.reorder_ajax_fail)}})}),
// This is probably best in a plugin or common area as
// we have more of these; keeping it here for now while
// we assess the requirements for new table lists
e(".tbl-list .check-ctrl input").click(function(){
// Check/uncheck the children of this category
e(this).parents(".tbl-list-item").first().find(".tbl-list .check-ctrl input").prop("checked",e(this).is(":checked")).trigger("change"),
// If we're unchecking something, make sure all its
// parents are also unchecked
e(this).is(":checked")||e(this).parents(".tbl-list-item").find("> .tbl-row > .check-ctrl input").prop("checked",!1).trigger("change")})})}(jQuery);