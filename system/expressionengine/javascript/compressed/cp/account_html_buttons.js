/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

$(document).ready(function(){$("#myaccountHtmlButtonsLink").show();$(".mainTable .tag_order input").hide();$(".mainTable tbody").sortable({axis:"y",containment:"parent",placeholder:"tablesize",start:function(){$(".submit input.submit").attr("disabled",true).addClass("disabled_field")},stop:function(){var a="";$(".mainTable input.tag_order").each(function(){a+="&"+$(this).attr("name")+"="+$(this).val()});$.ajax({type:"POST",url:EE.BASE+"&C=myaccount&M=reorder_html_buttons",data:"XID="+EE.XID+a,complete:function(){$(".submit input.submit").attr("disabled",
false).removeClass("disabled_field")},success:function(){$(".tag_order input[type=text]").each(function(b){$(this).val(b)})}})}});$(".del_row").show();$(".del_row a").click(function(){$.ajax({url:$(this).attr("href")});$(this).parent().parent().remove();return false});$("#add_new_html_button").hide();$(".del_instructions").hide();$(".cp_button").show().toggle(function(){$("#add_new_html_button").slideDown()},function(){$("#add_new_html_button").slideUp()})});
