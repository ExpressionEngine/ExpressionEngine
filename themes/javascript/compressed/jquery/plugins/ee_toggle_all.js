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

(function(c){c.fn.toggle_all=function(){function h(a){var b=a.find("tbody tr").get();a.data("table_config")&&a.bind("tableupdate",function(){b=a.table("get_current_data").html_rows;a.find("input:checkbox").prop("checked",!1)});this.getColumn=function(a){return c.map(b,function(b,c){return b.cells[a]})}}var l={$table:"",rowCache:"",column:0,tableCells:[],shift:!1,init:function(a,b,c){this.$table=a;this.rowCache=b;this.column=c;this.tableCells=this.rowCache.getColumn(this.column);this.checkboxListen();
this.tableListen();this.shiftListen()},checkboxListen:function(){var a=this;c(this.tableCells).each(function(b,d){c(this).find("input[type=checkbox]").unbind("click").click(function(d){currentlyChecked=a.checkboxChecked(b);if(a.shift&&!1!==currentlyChecked){d=currentlyChecked>b?b:currentlyChecked;var e=currentlyChecked>b?currentlyChecked:b;c(a.tableCells).slice(d,e).find("input[type=checkbox]").attr("checked",!0)}})})},tableListen:function(){var a=this;this.$table.bind("tableupdate",function(){a.tableCells=
a.rowCache.getColumn(a.column);a.checkboxListen()})},shiftListen:function(){var a=this;c(window).bind("keyup keydown",function(b){a.shift=b.shiftKey})},checkboxChecked:function(a){if(1<c(this.tableCells).find("input[type=checkbox]").not(":eq("+a+")").find(":checked").size())return!1;var b=0;c(this.tableCells).each(function(d,k){if(d!==a&&c(this).find("input[type=checkbox]").is(":checked"))return b=d,!1});return b}};return this.each(function(){var a=c(this),b={},d=new h(a);a.find("th").has("input:checkbox").each(function(k,
e){var f=this.cellIndex,g=c(this).find(":checkbox");c(this).click(function(a){var b=g.prop("checked");a.target!=g.get(0)&&(b=!b,g.prop("checked",b));a=d.getColumn(f);c(a).find(":checkbox").prop("checked",b)});b[f]=g;l.init(a,d,f)});a.delegate("td","click",function(a){var e=this.cellIndex,f=!0;if(!b[e]||!c(a.target).is(":checkbox"))return!0;if(!a.target.checked)return b[e].prop("checked",!1),!0;c.each(d.getColumn(e),function(){if(!c(this).find(":checkbox").prop("checked"))return f=!1});b[e].prop("checked",
f)})})}})(jQuery);
