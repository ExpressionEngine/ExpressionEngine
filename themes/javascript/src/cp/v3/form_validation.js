/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

(function($) {

$(document).ready(function() {

	EE.cp.formValidation.init();
});

EE.cp.formValidation = {

	init: function() {

		this._bindButtonStateChange();
		this._bindForms();
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

			that._sendAjaxRequest($(this));
		});

		$('input[type=checkbox], input[type=radio], select', container).change(function() {

			that._sendAjaxRequest($(this));
		});
	},

	/**
	 * Detects a form submission and changes the form's submit button
	 * to its working state
	 */
	_bindButtonStateChange: function() {

		// Bind form submission to update button text
		$('form').submit(function(event) {

			var $button = $('.form-ctrls input.btn', this);

			if ($button.size() > 0)
			{
				// Add "work" class to make the buttons pulsate
				$button.addClass('work');

				// Update the button text to the value of its "work-text"
				// data attribute
				if ($button.data('work-text') != '')
				{
					$button.attr('value', $button.data('work-text'));
				}
			}
		});
	},

	/**
	 * Binds forms with a class of 'ajax-validate' to the AJAX
	 * validation routines
	 */
	_bindForms: function() {

		var that = this;

		$('form').has('.form-ctrls input.btn').each(function(index, el) {

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
			var success = $('div.alert.success');
			
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

		var container = field.parents('div[class*=setting]'),
			fieldset = (container.parents('fieldset').size() > 0) ? container.parents('fieldset') : container.parent(),
			button = form.find('.form-ctrls input.btn'), // Submit button of form
			errorClass = 'em.ee-form-error-message',
			grid = false;

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

			// Re-enable submit button only if all errors are gone
			if ( ! this._errorsExist())
			{
				button.removeClass('disable')
					.attr('value', button.data('submit-text'))
					.removeAttr('disabled');
			}

		// Validation error
		} else {

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

			// Disable submit button
			button.addClass('disable').attr({
				value: EE.lang.btn_fix_errors,
				disabled: 'disabled'
			});
		}
	}
}

})(jQuery);
