/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

EE.cp.ModalForm = {

	saveAndNew: false,

	/**
	 * Opens a modal form
	 * @param  {object} options Object of options:
	 *   url - URL of form to load into the modal
	 *   full - If the form is to take the full screen width, set to true
	 *   iframe - If the form is to be loaded into an iframe, set to true
	 *   createUrl - URL of creation form for Save & New, if different than `url`
	 *   load - Callback to call on load of the URL contents into the modal
	 *   success - Callback to call on successful form submission
	 * @return {void}
	 */
	openForm: function(options) {
		this.modal = $('div[rel="modal-form"]')
		this.modalContents = $('.app-modal__content', this.modal)
		this.modalContentsContainer = $('div.contents', this.modal)
		this.modalCloseContainer = $('.app-modal__dismiss', this.modal)
		this.loadingBanner = $('.app-notice---loading', this.modal)
		this.titleBanner = $('.app-notice---attention', this.modal)

		var iframe = options.iframe || false,
			full = options.full || false

		this.modal.toggleClass('app-modal--side', ! full)
			.toggleClass('app-modal--fullscreen', iframe || full)
			.find('iframe')
			.remove()
		this.modalContents.toggle( ! iframe)
		this.loadingBanner.toggle(iframe)
		this.titleBanner.hide()

		this.modal.trigger('modal:open')
		this._loadModalContents(options)
		this._bindSaveAndNew()
	},

	/**
	 * Loads the modal form with the specified contents
	 */
	_loadModalContents: function(options) {
		if ( ! options.iframe) {
			var loading = $('<span />', { class: 'btn work'}).html('Loading')
			this.modalContentsContainer.html(loading)
		}

		var that = this
		if (options.iframe) {
			var iframe = $('<iframe />', {
				src: options.url + '&modal_form=y',
				class: 'app-modal__frame'
			}).on('load', function() {
				that.loadingBanner.hide()
				that._bindIframeForm(this, options)
				options.load(that.modal)
			})

			this.modal.append(iframe)
		} else {
			this.modalContentsContainer.load(options.url, function() {
				that._bindForm(options)
				options.load(that.modalContentsContainer)
			})

			// var timer = setInterval(function() {
			// 	if ($('.app-modal .grid-field').length) {
			// 		new Grid.Publish($('.app-modal .grid-field'))
			// 		clearInterval(timer);
			// 	}
			// },50);
		}
	},

	setTitle: function(title) {
		this.titleBanner.show().find('.app-notice__content p').html(title)
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

		if ($('.conditionset-item').length > 0) {
			// hide block if toggle is off
			if ($('#fieldset-field_is_conditional button.toggle-btn').hasClass('off')) {
				$('#fieldset-condition_fields').hide();
			}
			new Conditional.Publish($('.conditionset-item'));
		}

		$('form', this.modal).on('submit', function() {

			$.post($(this).attr('action'), $(this).serialize(), function(result) {
				// Probably a validation error
				if ($.type(result) === 'string') {
					that.modalContentsContainer.html(result)
					that._bindForm(options)
					options.load(that.modalContentsContainer)
					return
				} else if (options.success) {
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
	},

	/**
	 * Creates the form submit handler for a form loaded into an iframe
	 */
	_bindIframeForm: function(iframe, options) {
		$(iframe).contents().find(':not(.modal) form').on('submit', function() {
			var params = $(this).serialize() + '&modal_form=y';

			$.post(this.action, params, function(result) {
				// Probably a validation error
				if ($.type(result) === 'string') {
					iframe.contentDocument.open()
					iframe.contentDocument.write(result)
					iframe.contentDocument.close()
					options.load(that.modalContents)
					return
				} else if (result.redirect) {
					iframe.src = result.redirect
					return
				} else if (options.success) {
					options.success(result, that.modal)
				}
			})

			return false
		})

		var that = this
		$(iframe).contents().find('body').on('click', '.js-modal-close', function(e) {
			if (sessionStorage.getItem("preventNavigateAway") == 'true') {
				isNavigatingAway = confirm(EE.lang.confirm_exit);
				if (isNavigatingAway) {
					that.modal.trigger('modal:close')
				}
			} else {
				that.modal.trigger('modal:close')
			}
			e.preventDefault();
		})
		$(iframe).contents().find('body').on('keydown', function(e) {
			if (e.keyCode === 27) {
				if (sessionStorage.getItem("preventNavigateAway") == 'true') {
					isNavigatingAway = confirm(EE.lang.confirm_exit);
					if (isNavigatingAway) {
						that.modal.trigger('modal:close')
					}
				} else {
					that.modal.trigger('modal:close')
				}
				e.preventDefault();
			}
		});
	}
}
