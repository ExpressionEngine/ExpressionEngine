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

			that._registerTextInputs(form);
			that._registerNonTextInputs(form);
		});
	},

	/**
	 * Tells us whether or not there are any errors left on the form
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
	_errorsExist: function(form) {

		return ($('fieldset.invalid', form).size() != 0);
	},

	/**
	 * Text inputs will trigger a validation request on blur
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
	_registerTextInputs: function(form) {

		var that = this;

		$('input[type=text], input[type=password], textarea', form).blur(function() {

			that._sendAjaxRequest(form, $(this));
		});
	},

	/**
	 * Non-text inputs will trigger a validation request on change
	 *
	 * @param	{jQuery object}	form	jQuery object of form
	 */
	_registerNonTextInputs: function(form) {

		var that = this;

		$('input[type=checkbox], input[type=radio], select', form).change(function() {

			var obj = $(this);

			// If it's a checkbox, grab them all for validation
			if ($(this).is(':checkbox'))
			{
				obj = $('input[name="'+$(this).attr('name')+'"]');
			}

			that._sendAjaxRequest(form, obj);
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

		// Just reset the button for forms that don't validate over AJAX
		if ( ! form.hasClass('ajax-validate')) {
			this._toggleErrorForFields(form, field, 'success');
			return;
		}

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
