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

		// Build our alert box element
		this._alertBox = $('<div>', { class: 'alert inline issue'} );
		this._alertBox.append($('<h3>', { html: EE.lang.cp_message_issue } ));
		this._alertBox.append($('<p>', { html: EE.lang.form_validation_error } ));
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

		$('form.ajax-validate').each(function(index, el) {

			var form = $(this);
			that._registerNonTextInputs(form);
		});
	},

	/**
	 * Non-text inputs will trigger a validation request immediately
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
	_registerNonTextInputs: function(form) {

		var that = this;

		$('input[type=checkbox], input[type=radio], input[type=select]', form).change(function() {

			var name = $(this).attr('name');
			that._sendAjaxRequest(form, $('input[name='+name+']', form));
		});
	},

	/**
	 * Sends an AJAX request to the form's action, it's up to the form
	 * handler to detect that it's an AJAX request and handle the
	 * request differently
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 * @param	{jQuery object}	field	jQuery object of field validating
	 */
	_sendAjaxRequest: function(form, field) {

		var that = this,
			action = form.attr('action');
			data = field.add('input[name=csrf_token]', form).serialize();

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

		var fieldset = field.parents('fieldset'),
			button = form.find('.form-ctrls input.btn');

		// Validation success, return the form to its original, submittable state
		if (message == 'success') {

			fieldset.removeClass('invalid');
			fieldset.find('div.setting-field > em').remove();
			this._alertBox.remove();

			// Re-enable submit button
			button.removeClass('disable')
				.attr('value', button.data('submit-text'))
				.removeAttr('disabled');
		// Validation error
		} else {

			fieldset.addClass('invalid');

			// Don't double up on alert boxes
			if (form.has(this._alertBox).length == 0) {
				form.prepend(this._alertBox);
			}

			// Don't double up on error messages
			if (fieldset.has('em.ee-form-error-message').length) {
				fieldset.find('em.ee-form-error-message').html(message.error)
			} else {
				fieldset.find('div.setting-field').append(
					$('<em>', { class: 'ee-form-error-message', html: message.error })
				);
			}

			// Disable submit button
			button.addClass('disable').attr({
				value: EE.lang.btn_fix_errors,
				disabled: 'disabled'
			});
		}
	}
}

})(jQuery);
