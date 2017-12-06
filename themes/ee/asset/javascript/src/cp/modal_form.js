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
	saveAndNew: false,

	/**
	 * Opens a modal form
	 * @param  {object} options Object of options:
	 *   url - URL of form to load into the modal
	 *   createUrl - URL of creation form for Save & New, if different than `url`
	 *   load - Callback to call on load of the URL contents into the modal
	 *   success - Callback to call on successful form submission
	 * @return {void}
	 */
	openForm: function(options) {
		var that = this

		this.modal.trigger('modal:open')
		this._loadModalContents(options)
		this._bindSaveAndNew()
	},

	/**
	 * Loads the modal form with the specified contents
	 */
	_loadModalContents: function(options) {
		var that = this

		this.modalContentsContainer
			.html('<span class="btn work">Loading</span>')
			.load(options.url, function() {
				that._bindForm(options)
				options.load(that.modalContentsContainer)
			})
	},

	/**
	 * Tracks when Save & New is clicked so that we don't close the modal form
	 */
	_bindSaveAndNew: function(modal) {
		var that = this

		this.modal.on('click', 'button[value="save_and_new"]', function() {
			that.saveAndNew = true
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
					return
				} else {
					options.success(result)
				}

				if (that.saveAndNew) {
					// In case create form URL is different than original URL
					if (options.createUrl) {
						options.url = options.createUrl
					}
					that._loadModalContents(options)
				} else {
					that.modal.trigger('modal:close')
				}

				that.saveAndNew = false
			})

			return false;
		})
	}
}
