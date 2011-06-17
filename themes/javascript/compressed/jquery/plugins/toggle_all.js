/**
 * This jQuery plugin toggles all checkboxes in a table column when a checkbox 
 * in a table header is clicked
 *
 * Example usage:
 *	$('table').toggle_all();
 */

$.fn.toggle_all=function(){return this.each(function(){var a=$(this);$.each(a.find("th:has(input[type=checkbox])"),function(){var e=$(this),b=e.index(),c=e.find("input[type=checkbox]"),d=a.find("td:nth-child("+(b+1)+") input[type=checkbox]");c.click(function(){var a=$(this).is(":checked");d.attr("checked",a)});d.click(function(){d.size()==a.find("td:nth-child("+(b+1)+") input[type=checkbox]:checked").size()?c.attr("checked",!0):a.find("td:nth-child("+(b+1)+") input[type=checkbox]:checked").size()==
0&&c.attr("checked",!1)})})})};
