/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.3
 * @filesource
 */

(function(b){b.fn.toggle_all=function(){function h(a){var c=a.find("tbody tr").get();a.data("table_config")&&a.bind("tableupdate",function(){c=a.table("get_current_data").html_rows;a.find("input:checkbox").prop("checked",!1)});this.getColumn=function(a){return b.map(c,function(b){return b.cells[a]})}}return this.each(function(){var a=b(this),c={},f=new h(a);a.find("th").has("input:checkbox").each(function(){var a=this.cellIndex,d=b(this).find(":checkbox");b(this).click(function(c){var e=d.prop("checked");
c.target!=d.get(0)&&(e=!e,d.prop("checked",e));c=f.getColumn(a);b(c).find(":checkbox").prop("checked",e)});c[a]=d});a.delegate("td","click",function(a){var d=this.cellIndex,g=!0;if(!c[d]||!b(a.target).is(":checkbox"))return!0;if(!a.target.checked)return c[d].prop("checked",!1),!0;b.each(f.getColumn(d),function(){if(!b(this).find(":checkbox").prop("checked"))return g=!1});c[d].prop("checked",g)})})}})(jQuery);
