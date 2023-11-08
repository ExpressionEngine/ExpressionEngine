/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

EE.cp.BulkEdit = {

	modal: $('div[rel="modal-bulk-edit"]'),
	formContainer: $('.app-modal__content .col.w-12.remove-pad--right', this.modal),
	ajaxRequest: null,
	intent: null,

	intentFormUrls: {
		'bulk-edit': EE.publishEdit.bulkEditFormUrl,
		'add-categories': EE.publishEdit.addCategoriesFormUrl,
		'remove-categories': EE.publishEdit.removeCategoriesFormUrl
	},

	/**
	 * Opens a modal form
	 *
	 * @param {string} options Intent of modal
	 * @param {array} checked Array of jQuery objects of checked checkboxes for entries
	 * @return {void}
	 */
	openForm: function(intent, checked) {
		this.intent = intent

		var items = this._formatItems(checked)
		this._renderEntryList(items)

		this.formContainer.html('<span class="btn work">Loading</span>')
		this._loadForm(items)

		this.modal.trigger({
			type: 'modal:open',
			linkIs: 'js-modal--destruct'
		})
	},

	/**
	 * Renders the filterable entry list React component
	 *
	 * @param {array} items Array of item objects formatted for BulkEditEntries component
	 * @return {void}
	 */
	_renderEntryList: function(items) {
		var that = this
		BulkEditEntries.render(this.modal, {
			items: items,
			lang: EE.bulkEdit.lang,
			entriesChanged: function(items) {
				that._loadForm(items)
			}
		})
	},

	/**
	 * Given an array of jQuery objects of checked checkboxes, returns an array
	 * of items formatter for the BulkEditEntries component
	 *
	 * @param {array} checked Array of jQuery objects of checked checkboxes for entries
	 * @return {array}
	 */
	_formatItems: function(checked) {
		return $.map(checked, function(el) {
			return {
				label: $(el).data('title'),
				value: $(el).val()
			}
		})
	},

	/**
	 * Given an array of BulkEditEntries component-formatted items, returns an
	 * array of entry IDs for the items
	 *
	 * @param {array} items Array of BulkEditEntries component-formatted items
	 * @return {array}
	 */
	_getEntryIdsFromItems: function(items) {
		return $.map(items, function(item) {
			return item.value
		})
	},

	/**
	 * Loads the modal form with the specified contents
	 *
	 * @param {array} items Array of BulkEditEntries component-formatted items
	 * @return {array}
	 */
	_loadForm: function(items) {
		if (this.ajaxRequest) {
			this.ajaxRequest.abort()
		}

		if (items.length == 0) {
			return this.modal.trigger('modal:close')
		}

		var form = $('form', this.modal),
			entryIds = this._getEntryIdsFromItems(items),
			params = form.serialize() + '&' + $.param({ entry_ids: entryIds })

		var that = this
		this.ajaxRequest = $.ajax({
			url: this.intentFormUrls[this.intent],
			data: params,
			dataType: 'html',
			success: function(data) {
				that._bindForm(data, entryIds)
			},
			error: console.error
		})
	},

	/**
	 * Binds all necessary callbacks and events when the form markup loads
	 *
	 * @param {string} data HTML of form
	 * @param {array} entryIds Array of entry IDs we're editing
	 * @return {void}
	 */
	_bindForm: function(data, entryIds) {
		this.formContainer.html(data)

		this._bindAddField()
		this._bindRemoveField()
		this._enableOrDisableButtons()

		SelectField.renderFields(this.formContainer)
		Dropdown.renderFields(this.formContainer)
		EE.cp.datePicker.bind($('input[rel="date-picker"]'))

		this.formContainer.find('.fluid-field-templates :input')
			.attr('disabled', 'disabled')

		$.fuzzyFilter()
		var that = this
		this.formContainer.find('.fluid')
			.find('.js-sorting-container .fluid__item')
			.each(function(i, item) {
				that._toggleMenuItem($(item).data('fieldName'))
			})

		$('form', this.modal).on('submit', function() {
			var buttons = that.formContainer.find('.button')
			buttons.attr({
				value: buttons.data('work-text'),
				disabled: 'disabled'
			}).addClass('work')

			var params = $(this).serialize() + '&' + $.param({ entry_ids: entryIds })
			$.post(this.action, params, function(result) {
				// Probably a validation error
				if ($.type(result) === 'string') {
					that._bindForm(result, entryIds)
					return
				}

				location.reload()
			})
			return false
		})
	},

	/**
	 * Binds Fluid UI Add button
	 *
	 * @return {void}
	 */
	_bindAddField: function() {
		var that = this
		this.modal.off('click', '.fluid__footer a[data-field-name]')
		this.modal.on('click', '.fluid__footer a[data-field-name]', function(e) {
			var wrapper = $(this).closest('.fluid'),
				fieldName = $(this).data('fieldName'),
				template = wrapper.find('.fluid-field-templates [data-field-name="'+fieldName+'"]'),
				fieldContainer = wrapper.find('.js-sorting-container')

			// Add the field
			template.appendTo(fieldContainer)
			fieldContainer.find(':input').removeAttr('disabled')
			that._toggleMenuItem(fieldName, true)

			that._enableOrDisableButtons()

			// Close Add menu
			$('.js-dropdown-toggle.dropdown-open').trigger('click');

			// TODO: Once we have generic callback for fieldtypes to instantiate
			// their stuff in a future version, use that here instead
			SelectField.renderFields(fieldContainer)
			Dropdown.renderFields(fieldContainer)
			EE.cp.datePicker.bind($('input[rel="date-picker"]'))
		})
	},

	/**
	 * Binds Fluid UI Remove button
	 *
	 * @return {void}
	 */
	_bindRemoveField: function() {
		var that = this
		this.modal.on('click', '.js-fluid-remove', function(e) {
			e.preventDefault()

			var item = $(this).closest('.fluid__item')

			that._toggleMenuItem(item.data('fieldName'), false)
			item.appendTo(that.formContainer.find('.fluid-field-templates'))

			that.formContainer.find('.fluid-field-templates :input')
				.attr('disabled', 'disabled')

			that._enableOrDisableButtons()
		})
	},

	/**
	 * Toggle visibility of field name in Fluid UI Add menu
	 *
	 * @param {string} fieldName Short name of field
	 * @param {boolean} toggle Whether or not to hide or show the item
	 * @return {void}
	 */
	_toggleMenuItem: function(fieldName, toggle) {
		this.formContainer.find('.fluid__footer a[data-field-name="'+fieldName+'"]')
			.toggleClass('hidden', toggle)

		var allItems = this.formContainer.find('.fluid__footer a[data-field-name]')
		var hiddenItems = this.formContainer.find('.fluid__footer a[data-field-name].hidden')

		// Hide the add menu when there's no more filters to add
		if (allItems.length === hiddenItems.length) {
			this.formContainer.find('.fluid__footer').hide()
		} else {
			this.formContainer.find('.fluid__footer').show()
		}
	},

	/**
	 * Enables/disables submission buttons based on the presence of fields to submit
	 *
	 * @return {void}
	 */
	_enableOrDisableButtons: function() {

		// No Fluid field? Nothing to do
		if (this.modal.find('.fluid').length == 0) {
			return
		}

		var itemCount = this.formContainer.find('.js-sorting-container .fluid__item').length,
			buttons = this.formContainer.find('.button')

		if (itemCount == 0) {
			buttons.attr('disabled', 'disabled')
		} else {
			buttons.removeAttr('disabled')
		}
	}
}

})(jQuery);
