/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

$(document).ready(function() {
	EE.cp.formValidation.init();
});

EE.cp.formValidation = {

	paused: false,
	_validationCallbacks: [],

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

		// These are the text input selectors we listen to for activity
		this._textInputSelectors = 'input[type=text], input[type=number], input[type=password], textarea, div.redactor-styles, div.ck-content, div.condition-rule-field-wrap';
		this._buttonSelector = '.form-btns .button';

		form.each(function(index, el) {
			that._bindButtonStateChange($(el));
			that._bindForms($(el));
		});

		this._focusFirstError();
		this._scrollGrid();
		this._checkRequiredFields();
	},

	_checkRequiredFields: function() {
		var invalidFields = $('td.invalid, div.invalid');

		// check and removed `.invalid` from Fluid hidden templates block
		invalidFields.each(function(index, el) {
			if ( $(el).parents('.fluid-field-templates').length ) {
				$(el).removeClass('invalid');
				$(el).find('.ee-form-error-message').remove();
			}
		});
	},

	/**
	 * Bind inputs to the validation routine. Text inputs will trigger a
	 * validation request on blur, while others will trigger on change.
	 *
	 * @param	{jQuery object}	container	jQuery object of container of elements
	 */
	bindInputs: function(container) {

		var that = this;

		// Don't fire AJAX when submit button pressed
		$(container).on('mousedown', this._buttonSelector, function() {
			that.pause()
		})

		$(this._textInputSelectors, container)
			.not('*[data-ajax-validate=no]')
			.blur(function() {

			// Unbind keydown validation when the invalid field loses focus
			$(this).data('validating', false);
			var element = $(this);

			setTimeout(function() {
				that._sendAjaxRequest(element);
			}, 0);
		});

		$(container).on('change', 'input[type=checkbox], input[type=radio], input[type=hidden], input[type=range], select', function() {

			var element = $(this);

			if (element.data('ajaxValidate') == 'no') return

			setTimeout(function() {
				that._sendAjaxRequest(element);
			}, 0);
		});

		// Upon loading the page with invalid fields, bind the text field
		// timer to correct the validation as the user types (for AJAX
		// validation only)
		$('form.ajax-validate .fieldset-invalid, form.ajax-validate div.grid-publish:has(div.invalid)').each(function() {
			that._bindTextFieldTimer($(this));
		});
	},

	/**
	 * Given a field name, sets a callback for that field to get called on
	 * validation. Handy if you need to do extra processing or change anything
	 * about the default DOM manipulation that this library does upon validation
	 * success or failure. Only the root name of a field may be passed in. For
	 * example, if you have field[row][column], then `field` must be passed in.
	 * You'll then get a notificaiton for each field under that field's umbrella
	 * but also the object of the actual field being validated.
	 *
	 * @param	{string}	fieldName	Root name of field to get notified of validation for
	 * @param	{callback}	callback	Callback function for validation event
	 */
	bindCallbackForField: function(fieldName, callback) {
		this._validationCallbacks[fieldName] = callback;
	},

	/**
	 * Upon form validation error, set the focus on the first text field that
	 * has a validation error; specifically set the cursor at the end, as
	 * focus() will select the entire contents of the text box
	 */
	_focusFirstError: function() {

		// Get the first container that has a text input inside it, then get
		// the first text input
		var textInput = $('.fieldset-invalid')
			.has(this._textInputSelectors)
			.first()
			.find(this._textInputSelectors)
			.first();

		// Bail if no field to focus
		if (textInput.length == 0)
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

		if (inputContainer.parents('.grid-publish').length > 0)
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

		var $button = $(this._buttonSelector, form),
			that = this

		// Bind form submission to update button text
		form.submit(function(event) {

			if ($button.length > 0)
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
				$button.each(function(index, thisButton) {
					thisButton = $(thisButton);
					if (!thisButton.hasClass('dropdown-toggle') && thisButton.data('work-text') != '') {
						// Replace button text with working text and disable the button to prevent further clicks
						if (thisButton.is('input')) {
							thisButton.attr('value', thisButton.data('work-text'));
						} else if (thisButton.is('button')) {
							thisButton.text(thisButton.data('work-text'));
						}
					}
				});
			}

			return true;
		});
	},

	/**
	 * Binds forms to the AJAX validation routines
	 *
	 * @param	{jQuery object}	form	Optional jQuery object of form
	 */
	_bindForms: function(form) {

		var that = this;

		form.each(function(index, el) {
			that.bindInputs($(this));
		});
	},

	/**
	 * Tells us whether or not there are any errors left on the form
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
	_errorsExist: function(form) {
		return ($('.fieldset-invalid:visible, td.invalid:visible, div.invalid:visible', form).length != 0);
	},

	/**
	 * Sends an AJAX request to the form's action, it's up to the form
	 * handler to detect that it's an AJAX request and handle the
	 * request differently
	 *
	 * @param	{jQuery object}	field	jQuery object of field validating
	 */
	_sendAjaxRequest: function(field) {
		if (this.paused || field.attr('name') === undefined) {
			return;
		}

		var form = field.parents('form');

		// Just reset the button for forms that don't validate over AJAX
		if ( ! form.hasClass('ajax-validate')) {
			this._toggleErrorForFields(field, 'success');
			return;
		}

		var that = this,
			action = form.attr('action'),
			data = form.serialize();

		if($('.field-conditionset-wrapper').length) {
			var sets = $('#fieldset-condition_fields .field-conditionset-wrapper').find('.conditionset-item');
			var hiddenRuleInputs = sets.find('.rule.hidden');
			var hiddenMatchInputs = $('#fieldset-condition_fields .conditionset-item.hidden').find('.match-react-element');

			$.each(hiddenRuleInputs, function(key, value) {

				// check if input in hidden container was init and have attr disable
				var timer = setInterval(function() {
					if ($(value).find('input').prop('disabled')) {
						clearInterval(timer);
					} else {
						$(value).find('input').attr('disabled', 'disabled');
					}
				},50);
			});

			$.each(hiddenMatchInputs, function(key, value) {

				// check if input in hidden container was init and have attr disable
				var timer = setInterval(function() {
					if ($(value).find('input').prop('disabled')) {
						clearInterval(timer);
					} else {
						$(value).find('input').attr('disabled', 'disabled');
					}
				},50);
			});
		}

		$.ajax({
			url: action,
			data: data+'&ee_fv_field='+field.attr('name'),
			type: 'POST',
			dataType: 'json',
			success: function (ret) {
				that._toggleErrorForFields(field, ret);
			}
		});
	},

	/**
	 * Given a field, marks the field as valid in the UI
	 *
	 * @param	{jQuery object}	field	jQuery object of field
	 */
	markFieldValid: function(field) {
		this._toggleErrorForFields(field, 'success');
	},

	/**
	 * Given a field, marks the field as invalid in the UI with an error message
	 *
	 * @param	{jQuery object}	field	jQuery object of field
	 * @param	{mixed}			message	Error message to show by invalid field
	 */
	markFieldInvalid: function(field, message) {
		this._toggleErrorForFields(field, message);
	},

	/**
	 * Does all the UI DOM work necessary to mark a field (in)valid on the screen
	 *
	 * @param	{jQuery object}	field	jQuery object of field validating
	 * @param	{mixed}			message	Return from AJAX request, 'success' marks a field as valid
	 */
	_toggleErrorForFields: function(field, message) {

		if (message != 'success' && typeof(message.success) !== 'undefined' && message.success == 'success') {
			hidden_fields = message.hidden_fields;
			message = 'success';
		}

		var form = field.parents('form'),
			container = field.parents('.field-control'),
			fieldset = (container.parents('fieldset').length > 0) ? container.parents('fieldset') : container.parent(),
			errorClass = 'em.ee-form-error-message',
			grid = false;

		// Tabs
		var tab_container = field.parents('.tab'),
			tab_rel = (tab_container.length > 0) ? tab_container.attr('class').match(/t-\d+/) : '', // Grabs the tab identifier (ex: t-2)
			tab = $(tab_container).parents('.tab-wrap').find('a[rel="'+tab_rel+'"]'), // Tab link
			// See if this tab has its own submit button
			tab_has_own_button = (tab_container.length > 0 && tab_container.find(this._buttonSelector).length > 0),
			// Finally, grab the button of the current form
			button = (tab_has_own_button) ? tab_container.find(this._buttonSelector) : form.find(this._buttonSelector),
			tab_button = $(tab_container).parents('.tab-wrap').find('button[rel="'+tab_rel+'"]');

		// If we're in a Grid input, re-assign some things to apply classes
		// and show error messages in the proper places
		if (fieldset.hasClass('fieldset-faux'))
		{
			fieldset = fieldset.find('div.field-instruct');
			container = field.parents('td');
			grid = true;
		}

		if (fieldset.find('.grid-field').length > 0)
		{
			container = field.parents('td');
			grid = true;
		}
		if (fieldset.find('.conditionset-item:not(.hidden)').length > 0)
		{
			grid = true;
			container = field.parents('.condition-rule-field-wrap')
		}

		// Validation success, return the form to its original, submittable state
		if (message == 'success') {
			// For Grid, we also need to remove the class on the cell and do some
			// special handling of the invalid class on the Grid field label
			if (grid) {
				container.removeClass('fieldset-invalid');
				container.removeClass('invalid');

				// For Grid, only remove the invalid class from the label if no
				// more errors exist in the Grid
				if (fieldset.parent().find('td.invalid').length == 0) {
					fieldset.removeClass('fieldset-invalid');

					// Remove error message below Grid field
					container.parents('div.field-control').find('> ' + errorClass).remove();
				}

				if (fieldset.find('.fluid').length > 0 && !this._errorsExist(container)) {
					fieldset.parent().find(errorClass).remove();
				}

			} else {
				fieldset.removeClass('fieldset-invalid');
			}

			container.find('> ' + errorClass).remove();

			// If no more errors on this tab, remove invalid class from tab
			if (tab.length > 0 &&  ! this._errorsExist(tab_container))
			{
				tab.removeClass('invalid'); 
			}

			// If no more errors on this tab, remove invalid class from tab
			if (tab_button.length > 0 &&  ! this._errorsExist(tab_container))
			{
				tab_button.removeClass('invalid'); 
			}

			if (EE.hasOwnProperty('publish') && EE.publish.hasOwnProperty('has_conditional_fields') && EE.publish.has_conditional_fields && typeof(hidden_fields) !== 'undefined') {
				EE.cp.hide_show_entries_fields(hidden_fields);
			}

			// Re-enable submit button only if all errors are gone
			if ( ! this._errorsExist(form) || ( ! this._errorsExist(tab_container) && tab_has_own_button))
			{
				button.removeClass('disable').removeAttr('disabled');
				$('.saving-options').removeClass('disable').removeAttr('disabled');

				button.each(function(index, thisButton) {
					thisButton = $(thisButton);
					if (!thisButton.hasClass('dropdown-toggle')) {
						if (thisButton.is('input')) {
							thisButton.attr('value', decodeURIComponent(thisButton.data('submit-text')));
						} else if (thisButton.is('button')) {
							if (typeof(thisButton.data('submit-text')) != 'undefined') {
								thisButton.html(decodeURIComponent(thisButton.data('submit-text')));
							}
						}
					}
				});
			}

		// Validation error
		} else {
			// Bind timer for text fields to validate field while typing
			this._bindTextFieldTimer(container);

			fieldset.addClass('fieldset-invalid');

			// Specify the Grid cell the error is in
			if (grid) {
				container.addClass('invalid');

				fieldset.each(function(i, el){
					if (!$(el).parents('td.invalid').length && $(el).parent('.grid-field__column-label').length) {
						$(el).removeClass('fieldset-invalid');
					}
				});
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
			if (tab.length > 0)
			{
				tab.addClass('invalid');
			}

			// Disable submit button
			button.addClass('disable').attr('disabled', 'disabled');

			button.each(function(index, thisButton) {
				thisButton = $(thisButton);
				if (!thisButton.hasClass('dropdown-toggle')) {
					if (thisButton.is('input')) {
						thisButton.attr('value', EE.lang.btn_fix_errors);
					} else if (thisButton.is('button')) {
						thisButton.text(EE.lang.btn_fix_errors);
					}
				}
			})
		}

		// There may be callbacks for fields that need to do extra processing
		// on validation; check for those and call them
		if (field.attr('name')) {
			var cleanField = field.attr('name').replace(/\[.+?\]/g, '');
			if (this._validationCallbacks[cleanField] !== undefined) {
				this._validationCallbacks[cleanField](message == 'success', message.error, field);
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
			timer,
			inputs = $(this._textInputSelectors, container);

		// Don't double-up on bindings
		if (inputs.data('validating') === true)
		{
			return;
		}

		// Bind the timer on keydown and change
		inputs.data('validating', true).on('keydown change', function() {

			// Reset the timer, no need to validate if user is still typing
			if (timer !== undefined) {
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
