/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.3
 * @filesource
 */

(function(c){c.fn.toggle_all=function(){function h(a){var b=a.find("tbody tr").get();a.data("table_config")&&a.bind("tableupdate",function(){b=a.table("get_current_data").html_rows;a.find("input:checkbox").prop("checked",!1)});this.getColumn=function(a){return c.map(b,function(c,b){return c.cells[a]})}}return this.each(function(){var a=c(this),b={},g=new h(a);a.find("th").has("input:checkbox").each(function(a,d){var e=this.cellIndex,f=c(this).find(":checkbox");c(this).click(function(a){var b=f.prop("checked");
a.target!=f.get(0)&&(b=!b,f.prop("checked",b));a=g.getColumn(e);c(a).find(":checkbox").prop("checked",b)});b[e]=f});a.delegate("td","click",function(a){var d=this.cellIndex,e=!0;if(!b[d]||!c(a.target).is(":checkbox"))return!0;if(!a.target.checked)return b[d].prop("checked",!1),!0;c.each(g.getColumn(d),function(){if(!c(this).find(":checkbox").prop("checked"))return e=!1});b[d].prop("checked",e)})})}})(jQuery);
