/**
 * This jQuery plugin toggles all checkboxes in a table column when a checkbox 
 * in a table header is clicked
 *
 * Example usage:
 *	$('table').toggle_all();
 */

$.fn.toggle_all=function(){return this.each(function(){var a=$(this);$.each(a.find("th:has(input[type=checkbox])"),function(){var e=$(this),c=e.index(),d=e.find("input[type=checkbox]"),b=a.find("td:nth-child("+(c+1)+") input[type=checkbox]");console.log(b.size());d.click(function(){var a=$(this).is(":checked");b.attr("checked",a)});b.click(function(){b.size()==a.find("td:nth-child("+(c+1)+") input[type=checkbox]:checked").size()?d.attr("checked",!0):a.find("td:nth-child("+(c+1)+") input[type=checkbox]:checked").size()==
0&&d.attr("checked",!1)})})})};
