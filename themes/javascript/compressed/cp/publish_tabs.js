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

var selected_tab="";function get_selected_tab(){return selected_tab}function tab_focus(a){$(".menu_"+a).parent().is(":visible")||$("a.delete_tab[href=#"+a+"]").trigger("click");$(".tab_menu li").removeClass("current");$(".menu_"+a).parent().addClass("current");$(".main_tab").hide();$("#"+a).show();$(".main_tab").css("z-index","");$("#"+a).css("z-index","5");selected_tab=a;$(".main_tab").sortable("refreshPositions")}EE.tab_focus=tab_focus;
function setup_tabs(){var a="";$(".tab_menu li a").droppable({accept:".field_selector, .publish_field",tolerance:"pointer",forceHelperSize:!0,deactivate:function(c,b){clearTimeout(a);$(".tab_menu li").removeClass("highlight_tab")},drop:function(a,b){field_id=b.draggable.attr("id").substring(11);tab_id=$(this).attr("title").substring(5);setTimeout(function(){$("#hold_field_"+field_id).prependTo("#"+tab_id);$("#hold_field_"+field_id).hide().slideDown()},0);tab_focus(tab_id);return!1},over:function(c,
b){tab_id=$(this).attr("title").substring(5);$(this).parent().addClass("highlight_tab");a=setTimeout(function(){tab_focus(tab_id);return!1},500)},out:function(c,b){""!=a&&clearTimeout(a);$(this).parent().removeClass("highlight_tab")}});$("#holder .main_tab").droppable({accept:".field_selector",tolerance:"pointer",drop:function(a,b){field_id="hide_title"==b.draggable.attr("id")||"hide_url_title"==b.draggable.attr("id")?b.draggable.attr("id").substring(5):b.draggable.attr("id").substring(11);tab_id=
$(this).attr("id");$("#hold_field_"+field_id).prependTo("#"+tab_id);$("#hold_field_"+field_id).hide().slideDown()}});$(".tab_menu li.content_tab a, #publish_tab_list a.menu_focus").unbind(".publish_tabs").bind("mousedown.publish_tabs",function(a){tab_id=$(this).attr("title").substring(5);tab_focus(tab_id);a.preventDefault()}).bind("click.publish_tabs",function(){return!1});setTimeout(function(){$(".main_tab").sortable({connectWith:".main_tab",appendTo:"#holder",helper:"clone",forceHelperSize:!0,handle:".handle",
start:function(a,b){b.item.css("width",$(this).parent().css("width"))},stop:function(a,b){b.item.css("width","100%")}})},1500)}$(".tab_menu li:first").addClass("current");setup_tabs();
