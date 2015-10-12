!function(e){
// Load the RTE Builder
function t(t,o){e.getJSON(t,function(t){
// populate dialog innards
// show dialog
return t.error?void e.ee_notice(t.error,{type:"error"}):(r.find(".contents").html(t.success),void r.dialog("option","title",o).dialog("open"))})}
// Toolset Editor Modal
function o(){
// Cancel link
e("#rte-builder-closer").click(function(e){e.preventDefault(),r.dialog("close")}),e("#rte-tools-selected, #rte-tools-unused").sortable({connectWith:".rte-tools-connected",containment:".rte-toolset-builder",placeholder:"rte-tool-placeholder",revert:200,tolerance:"pointer",beforeStop:function(e,t){
// Replace the destination item with the item(s) in our helper container
t.item.replaceWith(t.helper.children().removeClass("rte-tool-active"))},helper:function(t,o){
// Make sure only items in *this* ul are highlighted
e(".rte-tools-connected").not(e(this)).children().removeClass("rte-tool-active"),
// Then make sure the item being dragged is actually highlighted
// Shouldn't this use ui.item? May be a bug.
o.addClass("rte-tool-active");
// jQuery UI doesn't (yet) provide a way to move multiple items, but
// we can achieve it by wrapping highlighted items as the helper
var r=e(".rte-tool-active");
// Shouldn't this use ui.item? May be a bug.
return r.length||(r=o.addClass("rte-tool-active")),e("<div/>").attr("id","rte-drag-helper").css("opacity",.7).width(e(o).outerWidth()).append(r.clone())},start:function(t,o){
// We use the helper during the drag operation, so hide the original
// highlighted elements and 'mark' them for removal
e(this).children(".rte-tool-active").hide().addClass("rte-tool-remove"),
// We don't want the placeholder to inherit this class
e(this).children(".ui-sortable-placeholder").removeClass("rte-tool-active")},stop:function(){
// Remove items that are marked for removal
e(".rte-tool-remove").remove(),
// Remove placeholder fix element* and re-add at end of both lists
e(".rte-placeholder-fix").remove(),e(".rte-tools-connected").append('<li class="rte-placeholder-fix"/>')}}),
// *So, there's a frustratingly common edge case where the drag placeholder
// appears *above* the last element in a list, but should appear *below* it
// because your pointer is clearly at the end of the list. Forcing a dummy
// li at the end of each list corrects this. Hacky, but... so is Droppable.
e(".rte-tools-connected").append('<li class="rte-placeholder-fix"/>'),
// AJAX submit handler
r.find("form").submit(function(t){t.preventDefault();var o=[];e("#rte-tools-selected .rte-tool").each(function(){o.push(e(this).data("tool-id"))}),
// populate field with selected tool ids
e("#rte-toolset-tools").val(o.join("|")),e.post(e(this).attr("action"),e(this).serialize(),function(t){return t.error?void e("#rte_toolset_editor_modal .notice").text(t.error):(e.ee_notice(t.success,{type:"success"}),r.dialog("close"),void(t.force_refresh&&(window.location=window.location)))},"json")})}var r=e('<div id="rte_toolset_editor_modal"><div class="contents"/></div>');
// make the modal
r.dialog({width:600,resizable:!1,position:["center","center"],modal:!0,draggable:!0,autoOpen:!1,zIndex:99999,open:function(e,t){o()}}),
// My Account - Toolset dropdown
e("body").on("change","#toolset_id",function(){var o=e(this).val();
// show the toolset editor if creating a custom toolset for the first time 
return"0"==o||o==EE.rte.my_toolset_id?(e("#edit_toolset").show(),void("0"==o&&t(EE.rte.url.edit_my_toolset.replace(/&amp;/g,"&")))):void e("#edit_toolset").hide()}),
// My Account - Fire dropdown change event once on load
e("#toolset_id").change(),
// My Account - Edit button (for My Toolset)
e("#edit_toolset").click(function(){t(EE.rte.url.edit_my_toolset.replace(/&amp;/g,"&"),EE.rte.lang.edit_my_toolset)}),
// Module home page
e("body").on("click",".edit_toolset",function(o){o.preventDefault();
// Editing or Creating?
var r="create_toolset"==this.id?EE.rte.lang.create_toolset:EE.rte.lang.edit_toolset;
// Load the RTE Builder
t(e(this).attr("href"),r)}),
// Enable toolset item selection/de-selection
r.on("click",".rte-tool",function(){e(this).toggleClass("rte-tool-active")})}(jQuery);