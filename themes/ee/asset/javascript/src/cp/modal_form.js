/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

EE.cp.ModalForm = {

	modal: $('div[rel="modal-form"]'),
	modalContentsContainer: $('div.contents', this.modal),

	/**
	 * Opens a modal form
	 * @param  {object} options Object of options:
	 *   url - URL of form to load into the modal
	 *   load - Callback to call on load of the URL contents into the modal
	 *   success - Callback to call on successful form submission
	 * @return {void}
	 */
	openForm: function(options) {
		var that = this

		this.modal.trigger('modal:open')
		this.modalContentsContainer.html('').load(options.url, function() {
			that._bindForm(options)
			options.load(that.modalContentsContainer)
		})
	},

	/**
	 * Creates the form submit handler and binds form validation to the loaded form
	 */
	_bindForm: function(options) {
		var that = this

		EE.cp.formValidation.init(this.modalContentsContainer.find('form'))

		$('form', this.modal).on('submit', function() {

			$.post(this.action, $(this).serialize(), function(result) {
				// Probably a validation error
				if ($.type(result) === 'string') {
					that.modalContentsContainer.html(result)
					that._bindForm(options)
					options.load(that.modalContentsContainer)
				} else {
					options.success(result)
					that.modal.trigger('modal:close')
				}
			})

			return false;
		})
	}
}
