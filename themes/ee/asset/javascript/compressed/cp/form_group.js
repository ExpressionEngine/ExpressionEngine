/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
!function(t){"use strict";function a(t,a,e){i(t,e,a),t.toggle(a)}function e(e,i,r){e.each(function(){t(this).toggle(i),t(this).nextUntil("h2, .form-ctrls").each(function(){var e=t(this),d=e.data("group");
// if we're showing this section, but the field is hidden
// from another toggle, then don't show it
d&&(o[d]=!i),i&&d?a(e,c[d],r):a(e,i,r)})})}
// This all kind of came about from needing to preserve radio button
// state for radio buttons but identical names across various groups.
// In an effort not to need to prefix those input names, we'll handle
// it automatically with this function.
function i(a,e,i){
//return;
a.find(":radio").each(function(){
//		var input = $(this),
//			name = input.attr('name'),
//			clean_name = (name) ? name.replace('el_disabled_'+group_name+'_', '') : '';
var a=t(this);
// Disable inputs that aren't shown, we don't need those in POST
a.attr("disabled",!i);var e=a.data("el_checked");e||(e="checked"==t(this).attr("checked"),a.data("el_checked",e),a.change(function(){a.data("el_checked",a.prop("checked"))})),i&&a.prop("checked",e)})}
// fields that are children of hidden parent fields
var o={"always-hidden":!1},c={"always-hidden":!1};t(document).ready(function(){var a=t("*[data-group-toggle]:radio");i(a,"",!1),
// loop through all of the toggles and record their current state
// we need this so that we can check if a section's visiblity should
// override the visibility of a child.
t("*[data-group-toggle]").each(function(a,e){if(!t(this).is(":radio")||t(this).is(":checked")){var i=t(this).data("groupToggle"),o=t(this).val();t.each(i,function(t,a){c[a]=!(t!=o)})}}),
// next go through and trigger our toggle on each to get the
// correct initial states. this cannot be combined with the
// above loop.
t("*[data-group-toggle]").each(function(a,e){if(!t(this).is(":radio")||t(this).is(":checked")){EE.cp.form_group_toggle(this);{t(this).data("groupToggle")}}})}),EE.cp.form_group_toggle=function(i){var r=t(i).data("groupToggle"),d=t(i).val();
// Show the selected group and enable its inputs
t.each(r,function(i,r){var n=t('*[data-group="'+r+'"]'),s=t('*[data-section-group="'+r+'"]');c[r]=i==d,a(n,o[r]?!1:i==d),e(s,i==d)});
// The reset the form .last values
var n=t(i).closest("form");n.find("fieldset.last").removeClass("last"),n.find("h2, .form-ctrls").each(function(){t(this).prevAll("fieldset:visible").first().addClass("last")})}}(jQuery);