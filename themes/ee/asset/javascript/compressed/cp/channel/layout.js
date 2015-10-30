/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
$(document).ready(function(){function e(){var e=$("ul.tabs a.act").parents("li").eq(0);return $("ul.tabs li").index(e)}function a(e){var a=$(e).parents("fieldset").eq(0);return $("div.tab-open fieldset").index(a)}function t(){$("ul.tabs li a").droppable({accept:"fieldset.sortable",hoverClass:"highlight",tolerance:"pointer",drop:function(a,t){if(
// Stop the Timeout
clearTimeout(i),
// Open the tab
$(this).trigger("click"),
// Remove the fieldset from the old tab
t.draggable.remove(),
// Add the fieldset to the new tab
$('<fieldset class="col-group sortable"></fieldset>').append(t.draggable.html()).prependTo($("div.tab-open")),$(t.draggable).hasClass("required")){$("div.tab-open fieldset:first-child").addClass("required");var s=$(this).closest("li");$(s).find(".tab-off").length>0&&$(s).find(".tab-off").trigger("click")}
// Add the field to the publish_layout array
EE.publish_layout[e()].fields.unshift(l),l=null,$("fieldset.sortable").removeClass("last"),$("fieldset.sortable:last-child").addClass("last")},over:function(e,a){tab=this,i=setTimeout(function(){$(tab).trigger("click"),$("div.tab").sortable("refreshPositions")},s)},out:function(e,a){clearTimeout(i)},deactivate:function(e,a){clearTimeout(i)}})}var l;
// Sorting the tabs
$("ul.tabs").sortable({cancel:"li:first-child",items:"li",start:function(e,a){tab_index_at_start=$("ul.tabs li").index(a.item[0])},update:function(e,a){var t=$("ul.tabs li").index(a.item[0]),l=EE.publish_layout.splice(tab_index_at_start,1);EE.publish_layout.splice(t,0,l[0]),tab_index_at_start=NaN}});var i,s=500;t(),
// Sorting the fields
$("div.tab").sortable({appendTo:"div.box.publish",connectWith:"div.tab",cursor:"move",forceHelperSize:!0,forcePlaceholderSize:!0,handle:"li.move a",helper:"clone",items:"fieldset.sortable",placeholder:"drag-placeholder",start:function(a,t){var i=$("div.tab-open fieldset").index(t.item[0]);l=EE.publish_layout[e()].fields.splice(i,1)[0],t.placeholder.append('<div class="none"></div>')},stop:function(a,t){if(t.position!=t.originalPosition){if(null!=l){var i=$("div.tab-open fieldset").index(t.item[0]);EE.publish_layout[e()].fields.splice(i,0,l),l=null}$("fieldset.sortable").removeClass("last"),$("fieldset.sortable:last-child").addClass("last")}}}),
// Saving the on/off state of tabs
$(".tab-on, .tab-off").on("click",function(e){var a=$(this).parents("li").eq(0),t=$("ul.tabs li").index(a),l=$("div.tab."+$(a).find("a").eq(0).attr("rel"));return EE.publish_layout[t].visible&&l.has(".required").length>0?void $("body").prepend(EE.alert.required.replace("%s",a.text())):(EE.publish_layout[t].visible=!EE.publish_layout[t].visible,$(this).toggleClass("tab-on tab-off"),void e.preventDefault())}),
// Adding a tab
$(".modal-add-new-tab button").on("click",function(e){var a=$('.modal-add-new-tab input[name="tab_name"]'),l=$('.modal-add-new-tab input[name="tab_name"]').val(),i="custom__"+l.replace(/ /g,"_").replace(/&/g,"and").toLowerCase(),s=/^[^*>:+()\[\]=|"'.#$]+$/;if(// allow all unicode characters except for css selectors and $
$(".modal-add-new-tab .setting-field em").remove(),a.parents("fieldset").removeClass("invalid"),""===l)
// Show the required_tab_name alert
a.after($("<em></em>").append(a.data("required"))),a.parents("fieldset").addClass("invalid");else if(s.test(l)){for(var d=!1,n=0;n<EE.publish_layout.length;n++)EE.publish_layout[n].id==i&&(d=!0);if(d)
// Show the duplicate_tab_name alert
a.after($("<em></em>").append(a.data("duplicate"))),a.parents("fieldset").addClass("invalid");else{var o={fields:[],id:i,name:l,visible:!0};EE.publish_layout.push(o);var r=$("ul.tabs li").length;$("ul.tabs li a").droppable("destroy"),$("ul.tabs").append('<li><a href="" rel="t-'+r+'">'+l+'</a> <span class="tab-remove"></span></li>'),$("div.tab.t-"+(r-1)).after('<div class="tab t-'+r+'"></div>'),t(),$(".modal-add-new-tab .m-close").trigger("click")}}else
// Show the illegal_tab_name alert
a.after($("<em></em>").append(a.data("illegal"))),a.parents("fieldset").addClass("invalid");e.preventDefault()}),$(".modal-add-new-tab .m-close").on("click",function(e){$('.modal-add-new-tab input[name="tab_name"]').val(""),$(".modal-add-new-tab .setting-field em").remove(),$('.modal-add-new-tab input[name="tab_name"]').parents("fieldset").removeClass("invalid")}),
// If you submit the form, trigger the submit button click
$(".modal-add-new-tab form").on("submit",function(e){$(".modal-add-new-tab .submit").trigger("click"),e.preventDefault()}),
// Removing a tab
$("ul.tabs").on("click",".tab-remove",function(e){var a=$(this).parents("li").eq(0),t=$("ul.tabs li").index(a),l=$("div.tab."+$(a).find("a").eq(0).attr("rel"));return l.html()?void $("body").prepend(EE.alert.not_empty.replace("%s",a.text())):(EE.publish_layout.splice(t,1),a.remove(),void l.remove())}),
// Saving the hide/unhide state of fields
$("div.publish form").on("click","li.hide a, li.unhide a",function(t){var l=e(),i=a(this);EE.publish_layout[l].fields[i].visible=!EE.publish_layout[l].fields[i].visible,$(this).parents("li").eq(0).toggleClass("hide unhide"),t.preventDefault()}),
// Saving the collapsed state
$(".sub-arrow").on("click",function(t){var l=e(),i=a(this);EE.publish_layout[l].fields[i].collapsed=!EE.publish_layout[l].fields[i].collapsed,t.preventDefault()}),$("div.publish form").on("submit",function(e){$('input[name="field_layout"]').val(JSON.stringify(EE.publish_layout))})});