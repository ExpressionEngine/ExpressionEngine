!function(t){/**
 * Grid Namespace
 */
var i=window.Grid={
// Event handlers stored here, direct access outside only from
// Grid.Publish class
_eventHandlers:[],/**
	 * Binds an event to a fieldtype
	 *
	 * Available events are:
	 * 'display' - When a row is displayed
	 * 'remove' - When a row is deleted
	 * 'beforeSort' - Before sort starts
	 * 'afterSort' - After sort ends
	 * 'displaySettings' - When settings form is displayed
	 *
	 * @param	{string}	fieldtypeName	Class name of fieldtype so the
	 *				correct cell object can be passed to the handler
	 * @param	{string}	action			Name of action
	 * @param	{func}		func			Callback function for event
	 */
bind:function(t,i,e){void 0==this._eventHandlers[i]&&(this._eventHandlers[i]=[]),
// Each fieldtype gets one method per handler
this._eventHandlers[i][t]=e}};/**
 * Grid Publish class
 *
 * @param	{string}	field		Selector of table to instantiate as a Grid
 */
i.Publish=function(i,e){this.root=t(i),this.blankRow=t("tr.grid-blank-row",this.root),this.emptyField=t("tr.no-results",this.root),this.rowContainer=this.root.not(t("tr",this.root).has("th")),this.settings=void 0!==e?e:EE.grid_field_settings[i.id],this.init(),this.eventHandlers=[]},i.Publish.prototype={init:function(){this._bindSortable(),this._bindAddButton(),this._bindDeleteButton(),this._toggleRowManipulationButtons(),this._fieldDisplay(),
// Store the original row count so we can properly increment new
// row placeholder IDs in _addRow()
this.original_row_count=this._getRows().size(),
// Disable input elements in our blank template container so they
// don't get submitted on form submission
this.blankRow.find(":input").attr("disabled","disabled")},/**
	 * Allows rows to be reordered
	 */
_bindSortable:function(){var t=this;this.root.eeTableReorder({
// Fire 'beforeSort' event on sort start
beforeSort:function(i){t._fireEvent("beforeSort",i.item)},
// Fire 'afterSort' event on sort stop
afterSort:function(i){t._fireEvent("afterSort",i.item)}})},/**
	 * Adds rows to a Grid field based on the fields minimum rows setting
	 * and how many rows already exist
	 */
_addMinimumRows:function(){
// Figure out how many rows we need to add
var t=this._getRows().size(),i=this.settings.grid_min_rows-t;
// Add the needed rows
for(
// Show empty field message if field is empty and no rows are needed
0==t&&0==i&&this.emptyField.show();i>0;)this._addRow(),i--},/**
	 * Toggles the visibility of the Add button and Delete buttons for rows
	 * based on the number of rows present and the max and min rows settings
	 */
_toggleRowManipulationButtons:function(){var i=this._getRows().size(),e=this.root.parents(".grid-publish").find(".toolbar .add a").parents("ul.toolbar"),n=this.root.find("th.reorder-col"),o=this.root.find("th.grid-remove");if(
// Show add button below field when there are more than zero rows
e.toggle(i>0),i>0?(
// Only show reorder header if table is configured to be reorderable
0==n.size()&&t("td.reorder-col",this.root).size()>0&&t("thead tr",this.root).prepend(t("<th/>",{"class":"first reorder-col"})),0==o.size()&&t("thead tr",this.root).append(t("<th/>",{"class":"last grid-remove"}))):(n.remove(),o.remove()),""!==this.settings.grid_max_rows&&
// Show add button if row count is below the max rows setting,
// and only if there are already other rows present
e.toggle(i<this.settings.grid_max_rows&&i>0),""!==this.settings.grid_min_rows){var s=this.root.find(".toolbar .remove");
// Show delete buttons if the row count is above the min rows setting
s.toggle(i>this.settings.grid_min_rows)}
// Do not allow sortable to run when there is only one row, otherwise
// the row becomes detached from the table and column headers change
// width in a fluid-column-width table
this.rowContainer.find("td.reorder-col").toggleClass("sort-cancel",1==i)},/**
	 * Returns current number of data rows in the Grid field, makes sure
	 * to skip counting of blank row, empty row and header row
	 *
	 * @return	{int}	Number of rows
	 */
_getRows:function(){return t("tr",this.rowContainer).not(this.blankRow.add(this.emptyField).add(t("tr",this.root).has("th")))},/**
	 * Binds click listener to Add button to insert a new row at the bottom
	 * of the field
	 */
_bindAddButton:function(){var t=this;this.root.parents(".grid-publish").find(".toolbar .add a").add(".no-results .btn",this.root).on("click",function(i){i.preventDefault(),t._addRow()})},/**
	 * Inserts new row at the bottom of our field
	 */
_addRow:function(){
// Clone our blank row
el=this.blankRow.clone(),el.removeClass("grid-blank-row"),el.removeClass("hidden"),el.show(),
// Increment namespacing on inputs
this.original_row_count++,el.html(el.html().replace(RegExp("new_row_[0-9]{1,}","g"),"new_row_"+this.original_row_count)),
// Enable inputs
el.find(":input").removeAttr("disabled"),
// Append the row to the end of the row container
this.rowContainer.append(el),
// Make sure empty field message is hidden
this.emptyField.hide(),
// Hide/show delete buttons depending on minimum row setting
this._toggleRowManipulationButtons(),
// Fire 'display' event for the new row
this._fireEvent("display",el),
// Bind the new row's inputs to AJAX form validation
EE.cp&&void 0!==EE.cp.formValidation&&EE.cp.formValidation.bindInputs(el)},/**
	 * Binds click listener to Delete button in row column to delete the row
	 */
_bindDeleteButton:function(){var i=this;this.root.on("click","td:last-child .toolbar .remove a",function(e){e.preventDefault(),row=t(this).parents("tr"),
// Fire 'remove' event for this row
i._fireEvent("remove",row),
// Remove the row
row.remove(),i._toggleRowManipulationButtons(),
// Show our empty field message if we have no rows left
0==i._getRows().size()&&i.emptyField.show()})},/**
	 * Called after main initialization to fire the 'display' event
	 * on pre-exising rows
	 */
_fieldDisplay:function(){var i=this;setTimeout(function(){i._getRows().each(function(){i._fireEvent("display",t(this))}),i._addMinimumRows()},500)},/**
	 * Fires event to fieldtype callbacks
	 *
	 * @param	{string}		action	Action name
	 * @param	{jQuery object}	row		jQuery object of affected row
	 */
_fireEvent:function(e,n){
// If no events regsitered, don't bother
if(void 0!==i._eventHandlers[e])
// For each fieldtype binded to this action
for(var o in i._eventHandlers[e])
// Find the sepecic cell(s) for this fieldtype and send each
// to the fieldtype's event hander
n.find('td[data-fieldtype="'+o+'"]').each(function(){i._eventHandlers[e][o](t(this))})}},/**
 * Grid Settings class
 */
i.Settings=function(i){this.root=t(".grid-wrap"),this.settingsScroller=this.root.find(".grid-clip"),this.settingsContainer=this.root.find(".grid-clip-inner"),this.colTemplateContainer=t("#grid_col_settings_elements"),this.blankColumn=this.colTemplateContainer.find(".grid-item"),this.settings=i,this.init()},i.Settings.prototype={init:function(){this._bindResize(),this._bindSortable(),this._bindActionButtons(this.root),this._toggleDeleteButtons(),this._bindColTypeChange(),
// If this is a new field, bind the automatic column title plugin
// to the first column
this._bindAutoColName(this.root.find('div.grid-item[data-field-name^="new_"]')),
// Fire displaySettings event
this._settingsDisplay(),
// Disable input elements in our blank template container so they
// don't get submitted on form submission
this.colTemplateContainer.find(":input").attr("disabled","disabled")},/**
	 * Upon page load, we need to resize the column container to fit the number
	 * of columns we have
	 */
_bindResize:function(){var i=this;t(document).ready(function(){i._resizeColContainer()})},/**
	 * Resizes column container based on how many columns it contains
	 *
	 * @param	{boolean}	animated	Whether or not to animate the resize
	 */
_resizeColContainer:function(t){this.settingsContainer.animate({width:this._getColumnsWidth()},1==t?400:0)},/**
	 * Calculates total width the columns in the container should take up,
	 * plus a little padding for the Add button
	 *
	 * @return	{int}	Calculated width
	 */
_getColumnsWidth:function(){var t=this.root.find(".grid-item");
// Actual width of column is width + 32
return t.size()*(t.width()+32)},/**
	 * Allows columns to be reordered
	 */
_bindSortable:function(){this.settingsContainer.sortable({axis:"x",// Only allow horizontal dragging
containment:"parent",// Contain to parent
handle:"li.reorder",// Set drag handle to the top box
items:".grid-item",// Only allow these to be sortable
sort:EE.sortable_sort_helper}),this.settingsContainer.find("li.reorder a").on("click",function(t){t.preventDefault()})},/**
	 * Convenience method for binding column manipulation buttons (add, copy, remove)
	 * for a given context
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
_bindActionButtons:function(t){this._bindAddButton(t),this._bindCopyButton(t),this._bindDeleteButton(t)},/**
	 * Binds click listener to Add button to insert a new column at the end
	 * of the columns
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
_bindAddButton:function(i){var e=this;i.find(".grid-tools li.add a").on("click",function(i){i.preventDefault();var n=t(this).parents(".grid-item");e._insertColumn(e._buildNewColumn(),n)})},/**
	 * Binds click listener to Copy button in each column to clone the column
	 * and insert it after the column being cloned
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
_bindCopyButton:function(i){var e=this;i.on("click",".grid-tools li.copy a",function(i){i.preventDefault();var n=t(this).parents(".grid-item");e._insertColumn(
// Build new column based on current column
e._buildNewColumn(n),
// Insert AFTER current column
n)})},/**
	 * Binds click listener to Delete button in each column to delete the column
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
_bindDeleteButton:function(i){var e=this;i.on("click",".grid-tools li.remove a",function(i){i.preventDefault();var n=t(this).parents(".grid-item");
// Only animate column deletion if we're not deleting the last column
n.index()==t(".grid-item:last",e.root).index()?(n.remove(),e._resizeColContainer(!0),e._toggleDeleteButtons()):n.animate({opacity:0},200,function(){
// Clear HTML before resize animation so contents don't
// push down bottom of column container while resizing
n.html(""),n.animate({width:0},200,function(){n.remove(),e._resizeColContainer(!0),e._toggleDeleteButtons()})})})},/**
	 * Looks at current column count, and if there are multiple columns,
	 * shows the delete buttons; otherwise, hides delete buttons if there is
	 * only one column
	 */
_toggleDeleteButtons:function(){var t=this.root.find(".grid-item").size(),i=this.root.find(".grid-tools li.remove");i.toggle(t>1)},/**
	 * Inserts a new column after a specified element
	 *
	 * @param	{jQuery Object}	column		Column to insert
	 * @param	{jQuery Object}	insertAfter	Element to insert the column
	 *				after; if left blank, defaults to last column
	 */
_insertColumn:function(i,e){var n=t(".grid-item:last",this.root);
// Default to inserting after the last column
void 0==e&&(e=n),
// If we're inserting a column in the middle of other columns,
// animate the insertion so it's clear where the new column is
e.index()!=n.index()&&i.css({opacity:0}),i.insertAfter(e),this._resizeColContainer(),this._toggleDeleteButtons(),
// If we are inserting a column after the last column, scroll to
// the end of the column container
e.index()==n.index()&&
// Scroll container to the very end
this.settingsScroller.animate({scrollLeft:this._getColumnsWidth()},700),i.animate({opacity:1},400),
// Bind automatic column name
this._bindAutoColName(i),
// Bind column manipulation buttons
this._bindActionButtons(i),
// Fire displaySettings event
this._fireEvent("displaySettings",t(".grid-col-settings-custom > div",i))},/**
	 * Binds ee_url_title plugin to column label box to auto-populate the
	 * column name field; this is only applied to new columns
	 *
	 * @param	{jQuery Object}	el	Column to bind ee_url_title to
	 */
_bindAutoColName:function(i){i.each(function(i,e){t("input.grid_col_field_label",e).bind("keyup keydown",function(){t(this).ee_url_title(t(e).find("input.grid_col_field_name"),!0)})})},/**
	 * Builts new column from scratch or based on an existing column
	 *
	 * @param	{jQuery Object}	el	Column to base new column off of, when
	 *				copying an existing column for example; if left blank,
	 *				defaults to blank column
	 * @return	{jQuery Object}	New column element
	 */
_buildNewColumn:function(i){i=void 0==i?this.blankColumn.clone():this._cloneWithFormValues(i),i.find('input[name$="\\[name\\]"]').attr("value","");
// Need to make sure the new column's field names are unique
var e="new_"+t(".grid-item",this.root).size();
// Make sure inputs are enabled if creating blank column
return i.html(i.html().replace(RegExp("(new_|col_id_)[0-9]{1,}","g"),e)),i.attr("data-field-name",e),i.find(":input").removeAttr("disabled").removeClass("grid_settings_error"),i},/**
	 * Binds change listener to the data type columns dropdowns of each column
	 * so we can load the correct settings form for the selected fieldtype
	 */
_bindColTypeChange:function(){var i=this;this.root.on("change","select.grid_col_select",function(e){
// New, fresh settings form
var n=i.colTemplateContainer.find(".grid_col_settings_custom_field_"+t(this).val()+":last").clone();
// Enable inputs
n.find(":input").removeAttr("disabled");var o=t(this).parents(".grid-item").find(".grid-col-settings-custom");
// Namespace fieldnames for the current column
n.html(n.html().replace(RegExp("(new_|col_id_)[0-9]{1,}","g"),o.parents(".grid-item").data("fieldName"))),
// Find the container holding the settings form, replace its contents
o.html(n),
// Fire displaySettings event
i._fireEvent("displaySettings",n)})},/**
	 * Clones an element and copies over any form input values because
	 * normal cloning won't handle that
	 *
	 * @param	{jQuery Object}	el	Element to clone
	 * @return	{jQuery Object}	Cloned element with form fields populated
	 */
_cloneWithFormValues:function(i){var e=i.clone();return i.find(":input:enabled").each(function(){
// Find the new input in the cloned column for editing
var i=e.find(":input[name='"+t(this).attr("name")+"']:enabled");t(this).is("select")?i.find("option").removeAttr("selected").filter('[value="'+t(this).val()+'"]').attr("selected","selected"):"checkbox"==t(this).attr("type")?
// .prop('checked', true) doesn't work, must set the attribute
i.attr("checked",t(this).attr("checked")):"radio"==t(this).attr("type")?i.removeAttr("selected").filter("[value='"+t(this).val()+"']").attr("checked",t(this).attr("checked")):t(this).is("textarea")?i.html(t(this).val()):
// .val('new val') doesn't work, must set the attribute
i.attr("value",t(this).val())}),e},/**
	 * Called after main initialization to fire the 'display' event
	 * on pre-exising columns
	 */
_settingsDisplay:function(){var i=this;this.root.find(".grid-item").each(function(){
// Fire displaySettings event
i._fireEvent("displaySettings",t(".grid-col-settings-custom > div",this))})},/**
	 * Fires event to fieldtype callbacks
	 *
	 * @param	{string}		action	Action name
	 * @param	{jQuery object}	el		jQuery object of affected element
	 */
_fireEvent:function(e,n){var o=n.data("fieldtype");
// If no events regsitered, don't bother
void 0!==i._eventHandlers[e]&&void 0!=i._eventHandlers[e][o]&&i._eventHandlers[e][o](t(n))}},/**
 * Public method to instantiate Grid field
 */
EE.grid=function(t,e){return new i.Publish(t,e)},/**
 * Public method to instantiate Grid settings
 */
EE.grid_settings=function(t){return new i.Settings(t)},"undefined"!=typeof _&&"undefined"!==EE.grid_cache&&_.each(EE.grid_cache,function(t){i.bind.apply(i,t)})}(jQuery);