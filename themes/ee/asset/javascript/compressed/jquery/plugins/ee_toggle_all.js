/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.3
 * @filesource
 */
/**
 * This jQuery plugin toggles all checkboxes in a table column when a checkbox
 * in a table header is clicked
 *
 * Example usage:
 *	$('table').toggle_all();
 */
!function(e){e.fn.toggle_all=function(){
// small abstraction for row / column access. We have it in here
// so that developers don't need to account for datatables changes.
function t(t){var c=t.find("tbody tr").get();
// bind to table events
t.data("table_config")&&t.bind("tableupdate",function(){c=t.table("get_current_data").html_rows,t.find("input:checkbox").prop("checked",!1).trigger("change")}),
// we always need columns ...
this.getColumn=function(t){return e.map(c,function(c,n){return e(c.cells[t]).has("input[type=checkbox]").size()?c.cells[t]:void 0})}}
// Handle shift+clicks for multiple checkbox selection
var c={$table:"",rowCache:"",column:0,tableCells:[],shift:!1,init:function(e,t,c){this.$table=e,this.rowCache=t,this.column=c,this.tableCells=this.rowCache.getColumn(this.column),this.checkboxListen(),this.tableListen(),this.shiftListen()},/**
			 * Listens for clicks on the checkboxes of the passed in table cells
			 */
checkboxListen:function(){var t=this;e(this.tableCells).each(function(c,n){e(this).find("input[type=checkbox]").unbind("click").click(function(n){if(currentlyChecked=t.checkboxChecked(c),t.shift&&currentlyChecked!==!1){var i=currentlyChecked>c?c:currentlyChecked,h=currentlyChecked>c?currentlyChecked:c;e(t.tableCells).slice(i,h).find("input[type=checkbox]").attr("checked",!0).trigger("change")}})})},/**
			 * Listen for changes to the table, recache the tableCells and
			 * rebind the checkboxes
			 */
tableListen:function(){var e=this;this.$table.bind("tableupdate",function(){e.tableCells=e.rowCache.getColumn(e.column),e.checkboxListen()})},/**
			 * Listen for the shift button and store the state
			 */
shiftListen:function(){var t=this;e(window).bind("keyup keydown",function(e){t.shift=e.shiftKey})},/**
			 * Check to see what the index of the first checked checkbox is, if
			 * its the only checkbox checked, then return false
			 *
			 * @param {integer} current The index of the clicked checkbox
			 * @return {mixed} Either false if there's only one checkbox checked
			 *                        or the index of the other checked checkbox
			 */
checkboxChecked:function(t){if(e(this.tableCells).find("input[type=checkbox]").not(":eq("+t+")").find(":checked").size()>1)return!1;var c=0;return e(this.tableCells).each(function(n,i){return n!==t&&e(this).find("input[type=checkbox]").is(":checked")?(c=n,!1):void 0}),c}};
// GO GO GO
// Standard jquery plugin procedure
// Process all matched tables
return this.each(function(){
// Simple object to hold header objects
var n={checkboxes:{},
// Add a checkbox, no way to overwrite
add:function(e,t){
// Make sure an array exists
return"undefined"==typeof this.checkboxes[e]&&(this.checkboxes[e]=[]),this.checkboxes[e].push(t),!0},
// Get an array of checkboxes for a given column
get:function(e){return this.checkboxes[e]},
// Iterate over a column of checkboxes
each:function(t,c){e.each(this.checkboxes[t],function(t,n){c.call(e(n),t,n)})}},i=e(this),h=new t(i);
// STEP 1:
// Loop through each selected header with a checkbox
// Listens to clicks on the checkbox and updates the
// row below to match its state.
i.find("th").has("input:checkbox").each(function(t,o){
// Name the table header, figure out it's index, get the header
// checkbox, and select all the data
var r=this.cellIndex,s=e(this).find(":checkbox");
// Listen for clicks to the header checkbox
e(this).on("click","input[type=checkbox]",function(t){var c=s.prop("checked");t.target!=s.get(0)&&(c=!c,s.prop("checked",c).trigger("change")),
// Check all normal checkboxes
e(h.getColumn(r)).find(":checkbox:enabled").prop("checked",c).trigger("change"),
// Check all header checkboxes
n.each(r,function(){e(this).prop("checked",c).trigger("change")})}),
// remember the headers
n.add(r,s),c.init(i,h,r)}),
// STEP 2:
// Listens to clicks on any checkbox in one of the
// checkbox columns and update the header checkbox's
// state to reflect the overall column.
i.delegate("td","click",function(t){var c=this.cellIndex,i=!0;
// does this column even have a header checkbox?
// was the click on a checkbox?
// does this column even have a header checkbox?
// was the click on a checkbox?
// run through the entire column to see if they're
// all checked or not
// set the header checkbox
// unchecked one, definitely not all checked
return n.get(c)&&e(t.target).is(":checkbox")?t.target.checked?(e.each(h.getColumn(c),function(){return e(this).find(":checkbox").prop("checked")?void 0:(i=!1,!1)}),void n.each(c,function(t,c){e(this).prop("checked",i).trigger("change")})):(n.each(c,function(t,c){e(this).prop("checked",!1).trigger("change")}),!0):!0})})}}(jQuery);