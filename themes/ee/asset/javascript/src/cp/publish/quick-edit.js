/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

EE.cp.QuickEdit = {

	modal: $('div[rel="modal-quick-edit"]'),
	formContainer: $('.app-modal__content .col.w-12.remove-pad--right', this.modal),
	ajaxRequest: null,
	intent: null,

	intentFormUrls: {
		'quick-edit': EE.publishEdit.quickEditFormUrl,
		'add-categories': EE.publishEdit.addCategoriesFormUrl,
		'remove-categories': EE.publishEdit.removeCategoriesFormUrl
	},

	/**
	 * Opens a modal form
	 * @param  {object} options Object of options:
	 *   url - URL of form to load into the modal
	 *   checked - Array of jQuery objects of checked checkboxes for entries
	 * @return {void}
	 */
	openForm: function(intent, checked) {
		this.intent = intent

		var items = this._formatItems(checked)
		this._renderEntryList(items)
		this._loadForm(items)

		this.modal.trigger('modal:open')
	},

	_renderEntryList(items) {
		var that = this
		QuickEditEntries.render(this.modal, {
			items: items,
			entriesChanged: function(items) {
				that._loadForm(items)
			}
		})
	},

	_formatItems: function(checked) {
		return $.map(checked, function(el) {
			return {
				label: $(el).data('title'),
				value: $(el).val(),
				meta: { channelId: $(el).data('channelId') }
			}
		})
	},

	_getEntryIdsFromItems: function(items) {
		return $.map(items, function(item) {
			return item.value
		})
	},

	/**
	 * Loads the modal form with the specified contents
	 */
	_loadForm: function(items) {
		this.formContainer.html('<span class="btn work">Loading</span>')

		if (this.ajaxRequest) {
			this.ajaxRequest.abort()
		}

		var that = this
		this.ajaxRequest = $.ajax({
			url: this.intentFormUrls[this.intent],
			data: $.param({ entryIds: this._getEntryIdsFromItems(items) }),
			dataType: 'html',
			success: function(data) {
				that._bindForm(data)
			},
			error: console.error
		})
	},

	/**
	 * Creates the form submit handler and binds form validation to the loaded form
	 */
	_bindForm: function(data) {
		var that = this
		that.formContainer.html(data)

		$('form', this.modal).on('submit', function() {
			$.post(this.action, $(this).serialize(), function(result) {
				// Probably a validation error
				if ($.type(result) === 'string') {
					that._bindForm(result)
					return
				}

				that.modal.trigger('modal:close')
			})
			return false
		})
	}
}
