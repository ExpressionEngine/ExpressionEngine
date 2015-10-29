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
!function(t){t(document).ready(function(){EE.cp.formValidation.init()}),EE.cp.formValidation={/**
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
init:function(e){var e=e||t("form"),i=this;e.each(function(e,s){i._bindButtonStateChange(t(s)),i._bindForms(t(s))}),this._focusFirstError(),this._scrollGrid()},/**
	 * Bind inputs to the validation routine. Text inputs will trigger a
	 * validation request on blur, while others will trigger on change.
	 *
	 * @param	{jQuery object}	container	jQuery object of container of elements
	 */
bindInputs:function(e){var i=this;t("input[type=text], input[type=password], textarea",e).blur(function(){
// Unbind keydown validation when the invalid field loses focus
t(this).unbind("keydown"),i._sendAjaxRequest(t(this))}),t("input[type=checkbox], input[type=radio], select",e).change(function(){i._sendAjaxRequest(t(this))}),
// Upon loading the page with invalid fields, bind the text field
// timer to correct the validation as the user types (for AJAX
// validation only)
t("form.ajax-validate fieldset.invalid").each(function(){i._bindTextFieldTimer(t(this))})},/**
	 * Upon form validation error, set the focus on the first text field that
	 * has a validation error; specifically set the cursor at the end, as
	 * focus() will select the entire contents of the text box
	 */
_focusFirstError:function(){
// Get the first container that has a text input inside it, then get
// the first text input
var e=t(".invalid").has("input[type=text], textarea").first().find("input[type=text], textarea").first();
// Bail if no field to focus
if(0!=e.size()){
// Multiply by 2 to ensure the cursor always ends up at the end;
// Opera sometimes sees a carriage return as 2 characters
var i=2*e.val().length;
// Focus and set cursor to the end of the string
e.focus(),e[0].setSelectionRange(i,i)}},/**
	 * If a field inside a Grid input has an error, the error could be off
	 * screen on smaller screens, so we'll scroll the Grid to the first field
	 * that has a problem
	 */
_scrollGrid:function(){var e=t(".invalid").has("input, select, textarea").first();if(e.parents(".grid-publish").size()>0){var i=e.position();e.parents(".tbl-wrap").scrollLeft(i.left)}},/**
	 * Detects a form submission and changes the form's submit button
	 * to its working state
	 *
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
_bindButtonStateChange:function(e){var i=t(".form-ctrls input.btn, .form-ctrls button.btn",e);
// Bind form submission to update button text
e.submit(function(s){
// Add "work" class to make the buttons pulsate
// If the submit was trigger by a button click, disable it to prevent futher clicks
// Update the button text to the value of its "work-text" data attribute
// Replace button text with working text and disable the button to prevent further clicks
return i.size()>0&&(i.addClass("work"),i.each(function(i,n){return s.target==n?(n.prop("disabled",!0),
// Some controllers rely on the presence of the submit button in POST, but it won't
// make it to the controller if it's disabled, so add it back as a hidden input
e.append(t("<input/>",{type:"hidden",name:n.name,value:n.value})),!1):void 0}),""!=i.data("work-text")&&(i.is("input")?i.attr("value",i.data("work-text")):i.is("button")&&s.target==el&&i.text(i.data("work-text")))),!0})},/**
	 * Binds forms with a class of 'ajax-validate' to the AJAX
	 * validation routines
	 *
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
_bindForms:function(e){var i=this;e.has(".form-ctrls .btn").each(function(e,s){var n=t(this);n.find(".form-ctrls input.btn");i.bindInputs(n),i._dismissSuccessAlert(n)})},/**
	 * When a form element is interacted with after the form has been
	 * successfully submitted, hide the success message
	 */
_dismissSuccessAlert:function(e){t("input, select, textarea",e).change(function(e){var i=t("div.alert.success");i.size()>0&&i.remove()})},/**
	 * Tells us whether or not there are any errors left on the form
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
_errorsExist:function(e){return 0!=t("fieldset.invalid, td.invalid",e).size()},/**
	 * Sends an AJAX request to the form's action, it's up to the form
	 * handler to detect that it's an AJAX request and handle the
	 * request differently
	 *
	 * @param	{jQuery object}	field	jQuery object of field validating
	 */
_sendAjaxRequest:function(e){var i=e.parents("form");
// Just reset the button for forms that don't validate over AJAX
if(!i.hasClass("ajax-validate"))return void this._toggleErrorForFields(i,e,"success");var s=this,n=i.attr("action"),a=i.serialize();t.ajax({url:n,data:a+"&ee_fv_field="+e.attr("name"),type:"POST",dataType:"json",success:function(t){s._toggleErrorForFields(i,e,t)}})},/**
	 * Shows/hides errors for fields based on result of validation
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 * @param	{jQuery object}	field	jQuery object of field validating
	 * @param	{mixed}			message	Return from AJAX request
	 */
_toggleErrorForFields:function(e,i,s){var n=i.parents("div[class*=setting]").not("div[class=setting-note]"),a=n.parents("fieldset").size()>0?n.parents("fieldset"):n.parent(),r="em.ee-form-error-message",d=!1,o=i.parents(".tab"),l=o.size()>0?o.attr("class").match(/t-\d+/):"",// Grabs the tab identifier (ex: t-2)
u=t(o).parents(".tab-wrap").find('a[rel="'+l+'"]'),// Tab link
// See if this tab has its own submit button
c=o.size()>0&&o.find(".form-ctrls input.btn").size()>0,
// Finally, grab the button of the current form
f=c?o.find(".form-ctrls input.btn"):e.find(".form-ctrls input.btn");
// Validation success, return the form to its original, submittable state
if(
// If we're in a Grid input, re-assign some things to apply classes
// and show error messages in the proper places
a.hasClass("grid-publish")&&(a=a.find("div.setting-txt"),n=i.parents("td"),d=!0),"success"==s)
// For Grid, we also need to remove the class on the cell and do some
// special handling of the invalid class on the Grid field label
d?(n.removeClass("invalid"),
// For Grid, only remove the invalid class from the label if no
// more errors exist in the Grid
0==a.parent().find("td.invalid").size()&&a.removeClass("invalid")):a.removeClass("invalid"),n.find("> "+r).remove(),
// If no more errors on this tab, remove invalid class from tab
u.size()>0&&!this._errorsExist(o)&&u.removeClass("invalid"),
// Re-enable submit button only if all errors are gone
(!this._errorsExist(e)||!this._errorsExist(o)&&c)&&(f.removeClass("disable").removeAttr("disabled"),f.is("input")?f.attr("value",f.data("submit-text")):f.is("button")&&f.text(f.data("submit-text")));else{
// Bind timer for text fields to validate field while typing
this._bindTextFieldTimer(n),a.addClass("invalid"),
// Specify the Grid cell the error is in
d&&n.addClass("invalid");
// We'll get HTML back from the validator, create an element
// out of it
var p=t("<div/>").html(s.error).contents();
// Don't double up on error messages
n.has(r).length&&n.find(r).remove(),n.append(p),
// Mark tab as invalid
u.size()>0&&u.addClass("invalid"),
// Disable submit button
f.addClass("disable").attr("disabled","disabled"),f.is("input")?f.attr("value",EE.lang.btn_fix_errors):f.is("button")&&f.text(EE.lang.btn_fix_errors)}},/**
	 * When a text field comes back as invalid, we'll bind a timer to it to
	 * check it's validity every half second after a key press, that way the
	 * user knows a field is fixed without having to remove focus from the field.
	 * Each key press resets the timer, so it's only when the keyboard has been
	 * inactive for a half second while the field is still in focus that the
	 * AJAX request to validate the form fires.
	 *
	 * @param	{jQuery object}	container	jQuery object of field's container
	 */
_bindTextFieldTimer:function(e){var i,s=this;
// Only bind to text fields
t("input[type=text], input[type=password], textarea",e).unbind("keydown").keydown(function(){
// Reset the timer, no need to validate if user is still typing
void 0!==i&&clearTimeout(i);var e=t(this);
// Wait half a second, then clear the timer and send the AJAX request
i=setTimeout(function(){clearTimeout(i),s._sendAjaxRequest(e)},500)})}}}(jQuery);