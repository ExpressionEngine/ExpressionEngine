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

(function($) {

$(document).ready(function() {
	EE.cp.formValidation.init();
});

EE.cp.formValidation = {

	paused: false,

	pause: function(noTimer) {
		this.paused = true;
		if (noTimer === undefined)
		{
			var that = this;
			setTimeout(function(){
				that.resume();
			}, 3000);
		}
	},

	resume: function() {
		this.paused = false;
	},

	/**
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
	init: function(form) {

		var form = form || $('form'),
			that = this;

		form.each(function(index, el) {

			that._bindButtonStateChange($(el));
			that._bindForms($(el));
		});

		this._focusFirstError();
		this._scrollGrid();
	},

	/**
	 * Bind inputs to the validation routine. Text inputs will trigger a
	 * validation request on blur, while others will trigger on change.
	 *
	 * @param	{jQuery object}	container	jQuery object of container of elements
	 */
	bindInputs: function(container) {

		var that = this;

		$('input[type=text], input[type=password], textarea', container).blur(function() {
			// Unbind keydown validation when the invalid field loses focus
			$(this).unbind('keydown');
			var element = $(this);

			setTimeout(function() {
				that._sendAjaxRequest(element);
			}, 0);
		});

		$('input[type=checkbox], input[type=radio], select', container).change(function() {
			var element = $(this);

			setTimeout(function() {
				that._sendAjaxRequest(element);
			}, 0);
		});

		// Upon loading the page with invalid fields, bind the text field
		// timer to correct the validation as the user types (for AJAX
		// validation only)
		$('form.ajax-validate fieldset.invalid').each(function() {
			that._bindTextFieldTimer($(this));
		});
	},

	/**
	 * Upon form validation error, set the focus on the first text field that
	 * has a validation error; specifically set the cursor at the end, as
	 * focus() will select the entire contents of the text box
	 */
	_focusFirstError: function() {

		// Get the first container that has a text input inside it, then get
		// the first text input
		var textInput = $('.invalid')
			.has('input[type=text], textarea')
			.first()
			.find('input[type=text], textarea')
			.first();

		// Bail if no field to focus
		if (textInput.size() == 0)
		{
			return;
		}

		// Multiply by 2 to ensure the cursor always ends up at the end;
		// Opera sometimes sees a carriage return as 2 characters
		var strLength = textInput.val().length * 2;

		// Focus and set cursor to the end of the string
		textInput.focus();
		textInput[0].setSelectionRange(strLength, strLength);
	},

	/**
	 * If a field inside a Grid input has an error, the error could be off
	 * screen on smaller screens, so we'll scroll the Grid to the first field
	 * that has a problem
	 */
	_scrollGrid: function() {

		var inputContainer = $('.invalid').has('input, select, textarea').first();

		if (inputContainer.parents('.grid-publish').size() > 0)
		{
			var position = inputContainer.position();
			inputContainer.parents('.tbl-wrap').scrollLeft(position.left);
		}
	},

	/**
	 * Detects a form submission and changes the form's submit button
	 * to its working state
	 *
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
	_bindButtonStateChange: function(form) {

		var $button = $('.form-ctrls input.btn, .form-ctrls button.btn', form);

		// Bind form submission to update button text
		form.submit(function(event) {

			if ($button.size() > 0)
			{
				// Add "work" class to make the buttons pulsate
				$button.addClass('work');

				// If the submit was trigger by a button click, disable it to prevent futher clicks
				$button.each(function(index, el) {
					if (event.target == el) {

						el.prop('disabled', true);

						// Some controllers rely on the presence of the submit button in POST, but it won't
						// make it to the controller if it's disabled, so add it back as a hidden input
						form.append($('<input/>', { type: 'hidden', name: el.name, value: el.value }));

						// Our work here is done
						return false;
					}
				});

				// Update the button text to the value of its "work-text" data attribute
				if ($button.data('work-text') != '')
				{
					// Replace button text with working text and disable the button to prevent further clicks
					if ($button.is('input')) {
						$button.attr('value', $button.data('work-text'));
					} else if ($button.is('button')) {
						$button.text($button.data('work-text'));
					}
				}
			}

			return true;
		});
	},

	/**
	 * Binds forms with a class of 'ajax-validate' to the AJAX
	 * validation routines
	 *
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
	_bindForms: function(form) {

		var that = this;

		form.has('.form-ctrls .btn').each(function(index, el) {

			var form = $(this),
				button = form.find('.form-ctrls input.btn');

			that.bindInputs(form);
			that._dismissSuccessAlert(form);
		});
	},

	/**
	 * When a form element is interacted with after the form has been
	 * successfully submitted, hide the success message
	 */
	_dismissSuccessAlert: function(form) {

		$('input, select, textarea', form).change(function(event) {
			var success = form.find('div.alert.success');

			if (success.size() > 0)
			{
				success.remove();
			}
		});
	},

	/**
	 * Tells us whether or not there are any errors left on the form
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
	_errorsExist: function(form) {

		return ($('fieldset.invalid, td.invalid', form).size() != 0);
	},

	/**
	 * Sends an AJAX request to the form's action, it's up to the form
	 * handler to detect that it's an AJAX request and handle the
	 * request differently
	 *
	 * @param	{jQuery object}	field	jQuery object of field validating
	 */
	_sendAjaxRequest: function(field) {
		if (this.paused) {
			return;
		}

		var form = field.parents('form');

		// Just reset the button for forms that don't validate over AJAX
		if ( ! form.hasClass('ajax-validate')) {
			this._toggleErrorForFields(form, field, 'success');
			return;
		}

		var that = this,
			action = form.attr('action'),
			data = form.serialize();

		$.ajax({
			url: action,
			data: data+'&ee_fv_field='+field.attr('name'),
			type: 'POST',
			dataType: 'json',
			success: function (ret) {
				that._toggleErrorForFields(form, field, ret);
			}
		});
	},

	/**
	 * Shows/hides errors for fields based on result of validation
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 * @param	{jQuery object}	field	jQuery object of field validating
	 * @param	{mixed}			message	Return from AJAX request
	 */
	_toggleErrorForFields: function(form, field, message) {

		var container = field.parents('div[class*=setting]').not('div[class=setting-note]'),
			fieldset = (container.parents('fieldset').size() > 0) ? container.parents('fieldset') : container.parent(),
			errorClass = 'em.ee-form-error-message',
			grid = false;

		// Tabs
		var tab_container = field.parents('.tab'),
			tab_rel = (tab_container.size() > 0) ? tab_container.attr('class').match(/t-\d+/) : '', // Grabs the tab identifier (ex: t-2)
			tab = $(tab_container).parents('.tab-wrap').find('a[rel="'+tab_rel+'"]'), // Tab link
			// See if this tab has its own submit button
			tab_has_own_button = (tab_container.size() > 0 && tab_container.find('.form-ctrls input.btn').size() > 0),
			// Finally, grab the button of the current form
			button = (tab_has_own_button) ? tab_container.find('.form-ctrls input.btn') : form.find('.form-ctrls input.btn');

		// If we're in a Grid input, re-assign some things to apply classes
		// and show error messages in the proper places
		if (fieldset.hasClass('grid-publish'))
		{
			fieldset = fieldset.find('div.setting-txt');
			container = field.parents('td');
			grid = true;
		}

		// Validation success, return the form to its original, submittable state
		if (message == 'success') {

			// For Grid, we also need to remove the class on the cell and do some
			// special handling of the invalid class on the Grid field label
			if (grid) {
				container.removeClass('invalid');

				// For Grid, only remove the invalid class from the label if no
				// more errors exist in the Grid
				if (fieldset.parent().find('td.invalid').size() == 0) {
					fieldset.removeClass('invalid');
				}
			} else {
				fieldset.removeClass('invalid');
			}

			container.find('> ' + errorClass).remove();

			// If no more errors on this tab, remove invalid class from tab
			if (tab.size() > 0 &&  ! this._errorsExist(tab_container))
			{
				tab.removeClass('invalid');
			}

			// Re-enable submit button only if all errors are gone
			if ( ! this._errorsExist(form) || ( ! this._errorsExist(tab_container) && tab_has_own_button))
			{
				button.removeClass('disable').removeAttr('disabled');

				button.each(function(index, thisButton) {
					thisButton = $(thisButton);
					if (thisButton.is('input')) {
						thisButton.attr('value', thisButton.data('submit-text'));
					} else if (thisButton.is('button')) {
						thisButton.text(thisButton.data('submit-text'));
					}
				});
			}

		// Validation error
		} else {

			// Bind timer for text fields to validate field while typing
			this._bindTextFieldTimer(container);

			fieldset.addClass('invalid');

			// Specify the Grid cell the error is in
			if (grid) {
				container.addClass('invalid');
			}

			// We'll get HTML back from the validator, create an element
			// out of it
			var errorElement = $('<div/>').html(message.error).contents();

			// Don't double up on error messages
			if (container.has(errorClass).length) {
				container.find(errorClass).remove();
			}

			container.append(errorElement);

			// Mark tab as invalid
			if (tab.size() > 0)
			{
				tab.addClass('invalid');
			}

			// Disable submit button
			button.addClass('disable').attr('disabled', 'disabled');

			if (button.is('input')) {
				button.attr('value', EE.lang.btn_fix_errors);
			} else if (button.is('button')) {
				button.text(EE.lang.btn_fix_errors);
			}
		}
	},

	/**
	 * When a text field comes back as invalid, we'll bind a timer to it to
	 * check it's validity every half second after a key press, that way the
	 * user knows a field is fixed without having to remove focus from the field.
	 * Each key press resets the timer, so it's only when the keyboard has been
	 * inactive for a half second while the field is still in focus that the
	 * AJAX request to validate the form fires.
	 *
	 * @param	{jQuery object}	container	jQuery object of field's container
	 */
	_bindTextFieldTimer: function(container) {

		var that = this,
			timer;

		// Only bind to text fields
		$('input[type=text], input[type=password], textarea', container).unbind('keydown').keydown(function() {

			// Reset the timer, no need to validate if user is still typing
			if (timer !== undefined)
			{
				clearTimeout(timer);
			}

			var field = $(this);

			// Wait half a second, then clear the timer and send the AJAX request
			timer = setTimeout(function() {
				clearTimeout(timer);
				that._sendAjaxRequest(field);
			}, 500);
		});
	}
}

})(jQuery);
