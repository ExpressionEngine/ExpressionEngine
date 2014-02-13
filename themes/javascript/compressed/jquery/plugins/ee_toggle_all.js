/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.3
 * @filesource
 */

(function(c){c.fn.toggle_all=function(){function h(a){var b=a.find("tbody tr").get();a.data("table_config")&&a.bind("tableupdate",function(){b=a.table("get_current_data").html_rows;a.find("input:checkbox").prop("checked",!1)});this.getColumn=function(a){return c.map(b,function(b){return b.cells[a]})}}var i={$table:"",rowCache:"",column:0,tableCells:[],shift:!1,init:function(a,b,c){this.$table=a;this.rowCache=b;this.column=c;this.tableCells=this.rowCache.getColumn(this.column);this.checkboxListen();
this.tableListen();this.shiftListen()},checkboxListen:function(){var a=this;c(this.tableCells).each(function(b){c(this).find("input[type=checkbox]").unbind("click").click(function(){currentlyChecked=a.checkboxChecked(b);if(a.shift&&!1!==currentlyChecked){var e=currentlyChecked>b?b:currentlyChecked,f=currentlyChecked>b?currentlyChecked:b;c(a.tableCells).slice(e,f).find("input[type=checkbox]").attr("checked",!0)}})})},tableListen:function(){var a=this;this.$table.bind("tableupdate",function(){a.tableCells=
a.rowCache.getColumn(a.column);a.checkboxListen()})},shiftListen:function(){var a=this;c(window).bind("keyup keydown",function(b){a.shift=b.shiftKey})},checkboxChecked:function(a){if(1<c(this.tableCells).find("input[type=checkbox]").not(":eq("+a+")").find(":checked").size())return!1;var b=0;c(this.tableCells).each(function(e){if(e!==a&&c(this).find("input[type=checkbox]").is(":checked"))return b=e,!1});return b}};return this.each(function(){var a=c(this),b={},e=new h(a);a.find("th").has("input:checkbox").each(function(){var f=
this.cellIndex,d=c(this).find(":checkbox");c(this).click(function(a){var b=d.prop("checked");a.target!=d.get(0)&&(b=!b,d.prop("checked",b));a=e.getColumn(f);c(a).find(":checkbox").prop("checked",b)});b[f]=d;i.init(a,e,f)});a.delegate("td","click",function(a){var d=this.cellIndex,g=!0;if(!b[d]||!c(a.target).is(":checkbox"))return!0;if(!a.target.checked)return b[d].prop("checked",!1),!0;c.each(e.getColumn(d),function(){if(!c(this).find(":checkbox").prop("checked"))return g=!1});b[d].prop("checked",
g)})})}})(jQuery);
