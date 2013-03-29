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

$(document).ready(function(){function a(){"yyyy-mm-dd"!=$("#custom_date_start").val()&&"yyyy-mm-dd"!=$("#custom_date_end").val()&&(focus_number=$("#date_range").children().length,$("#date_range").append('<option id="custom_date_option">'+$("#custom_date_start").val()+" to "+$("#custom_date_end").val()+"</option>"),document.getElementById("date_range").options[focus_number].selected=!0,$("#custom_date_picker").slideUp("fast"),oTable.fnDraw())}$(".paginationLinks .first").hide();$(".paginationLinks .previous").hide();
$(".toggle_all").toggle(function(){$("input.toggle").each(function(){this.checked=!0})},function(){$("input.toggle").each(function(){this.checked=!1})});$("#custom_date_start_span").datepicker({dateFormat:"yy-mm-dd",prevText:"<<",nextText:">>",onSelect:function(b){$("#custom_date_start").val(b);a()}});$("#custom_date_end_span").datepicker({dateFormat:"yy-mm-dd",prevText:"<<",nextText:">>",onSelect:function(b){$("#custom_date_end").val(b);a()}});$("#custom_date_start, #custom_date_end").focus(function(){"yyyy-mm-dd"==
$(this).val()&&$(this).val("")});$("#custom_date_start, #custom_date_end").keypress(function(){9<=$(this).val().length&&a()});var c=EE.edit.channelInfo,f=RegExp("!-!","g");(new Date).getTime();jQuery.each(c,function(b,a){jQuery.each(a,function(a,d){var e=new String;jQuery.each(d,function(b,a){e+='<option value="'+a[0]+'">'+a[1].replace(f,String.fromCharCode(160))+"</option>"});c[b][a]=e})});$("#f_channel_id").change(function(){var a=this.value;void 0===c[a]&&(a=0);jQuery.each(c[a],function(a,b){switch(a){case "categories":$("select#f_cat_id").empty().append(b);
break;case "statuses":$("select#f_status").empty().append(b)}})});$("#date_range").change(function(){"custom_date"==$("#date_range").val()?($("#custom_date_start").val("yyyy-mm-dd"),$("#custom_date_end").val("yyyy-mm-dd"),$("#custom_date_option").remove(),$("#custom_date_picker").slideDown("fast")):$("#custom_date_picker").hide()});$("#entries_form").submit(function(){if(!$("input:checkbox",this).is(":checked"))return $.ee_notice(EE.lang.selection_required,{type:"error"}),!1});var d=$(".searchIndicator");
$("table").table("add_filter",$("#keywords").closest("form")).bind("tableload",function(){d.css("visibility","")}).bind("tableupdate",function(){d.css("visibility","hidden")})});
