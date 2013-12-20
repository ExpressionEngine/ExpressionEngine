/*
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

$(document).ready(function(){function f(a){void 0===b[a]&&(a=0);jQuery.each(b[a],function(a,b){switch(a){case "categories":$("select#f_cat_id").empty().append(b);break;case "statuses":$("select#f_status").empty().append(b)}})}function c(){"yyyy-mm-dd"!=$("#custom_date_start").val()&&"yyyy-mm-dd"!=$("#custom_date_end").val()&&(focus_number=$("#date_range").children().length,$("#date_range").append('<option id="custom_date_option">'+$("#custom_date_start").val()+" to "+$("#custom_date_end").val()+"</option>"),
document.getElementById("date_range").options[focus_number].selected=!0,$("#custom_date_picker").slideUp("fast"),$("#date_range").change())}$(".paginationLinks .first").hide();$(".paginationLinks .previous").hide();$(".toggle_all").toggle(function(){$("input.toggle").each(function(){this.checked=!0})},function(){$("input.toggle").each(function(){this.checked=!1})});$("#custom_date_start_span").datepicker({dateFormat:"yy-mm-dd",prevText:"<<",nextText:">>",onSelect:function(a){$("#custom_date_start").val(a);
c()}});$("#custom_date_end_span").datepicker({dateFormat:"yy-mm-dd",prevText:"<<",nextText:">>",onSelect:function(a){$("#custom_date_end").val(a);c()}});$("#custom_date_start, #custom_date_end").focus(function(){"yyyy-mm-dd"==$(this).val()&&$(this).val("")});$("#custom_date_start, #custom_date_end").keypress(function(){9<=$(this).val().length&&c()});var b=EE.edit.channelInfo,g=RegExp("!-!","g");(new Date).getTime();(function(){jQuery.each(b,function(a,c){jQuery.each(c,function(c,d){var e=new String;
jQuery.each(d,function(a,b){e+='<option value="'+b[0]+'">'+b[1].replace(g,String.fromCharCode(160))+"</option>"});b[a][c]=e})})})();$("#f_channel_id").change(function(){f(this.value)});$("#date_range").change(function(){"custom_date"==$("#date_range").val()?($("#custom_date_start").val("yyyy-mm-dd"),$("#custom_date_end").val("yyyy-mm-dd"),$("#custom_date_option").remove(),$("#custom_date_picker").slideDown("fast")):$("#custom_date_picker").hide()});$("#entries_form").submit(function(){if(!$("input:checkbox",
this).is(":checked"))return $.ee_notice(EE.lang.selection_required,{type:"error"}),!1});var d=$(".searchIndicator");$("table").table("add_filter",$("#keywords").closest("form")).bind("tableload",function(){d.css("visibility","")}).bind("tableupdate",function(){d.css("visibility","hidden")})});
