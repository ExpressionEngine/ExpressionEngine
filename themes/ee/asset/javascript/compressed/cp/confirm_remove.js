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
$(document).ready(function(){$("*[data-conditional-modal]").click(function(t){var a=$(this).data("conditional-modal"),e=$(this).data("confirm-ajax"),i=$(this).data("confirm-text"),n=$(this).data("confirm-input"),l=$("*[data-"+a+"]").eq(0);if($(l).prop($(l).data(a))){
// First adjust the checklist
var c="."+$(l).attr("rel");$(c+" .checklist").html(""),// Reset it
"undefined"!=typeof i&&$(c+" .checklist").append("<li>"+i+"</li>");var d=$(this).parents("form").find("td input:checked, li input:checked");d.length<6?d.each(function(){$(c+" .checklist").append("<li>"+$(this).attr("data-confirm")+"</li>")}):$(c+" .checklist").append("<li>"+EE.lang.remove_confirm.replace("###",d.length)+"</li>"),
// Add hidden <input> elements
d.each(function(){$(c+" .checklist li:last").append('<input type="hidden" name="'+$(this).attr("name")+'" value="'+$(this).val()+'">')}),"undefined"!=typeof n&&$("input[name='"+n+"']").each(function(){$(c+" .checklist li:last").append('<input type="hidden" name="'+$(this).attr("name")+'" value="'+$(this).val()+'">')}),$(c+" .checklist li:last").addClass("last"),"undefined"!=typeof e&&$.post(e,$(c+" form").serialize(),function(t){$(c+" .ajax").html(t)});var o=$(document).height();$(".overlay").fadeIn("slow").css("height",o),$(".modal-wrap"+c).fadeIn("slow"),t.preventDefault(),$("#top").animate({scrollTop:0},100)}})});