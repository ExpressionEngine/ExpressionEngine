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

		this.formContainer.html('<span class="btn work">Loading</span>')
		this._loadForm(items)

		this._bindAddField()
		this._bindRemoveField()

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
		if (this.ajaxRequest) {
			this.ajaxRequest.abort()
		}

		if (items.length == 0) {
			return this.modal.trigger('modal:close')
		}

		var form = $('form', this.modal)
		var params = Object.assign(
			this._getFormData(form),
			{ entryIds: this._getEntryIdsFromItems(items) }
		)

		var that = this
		this.ajaxRequest = $.ajax({
			url: this.intentFormUrls[this.intent],
			data: $.param(params),
			dataType: 'html',
			success: function(data) {
				that._bindForm(data)
			},
			error: console.error
		})
	},

	_getFormData: function(form) {
		var formData = {}

		$.each(form.serializeArray(), function(i, input){
			formData[input.name] = input.value
		})

		return formData
	},

	/**
	 * Creates the form submit handler and binds form validation to the loaded form
	 */
	_bindForm: function(data) {
		this.formContainer.html(data)
		SelectField.renderFields(this.formContainer)
		this.formContainer.find('.fluid-field-templates :input')
			.attr('disabled', 'disabled')

		$.fuzzyFilter()
		var that = this
		this.formContainer.find('.fluid-wrap')
			.find('.js-sorting-container .fluid-item')
			.each(function(i, item) {
				that._toggleMenuItem($(item).data('fieldName'))
			})

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
	},

	_bindAddField: function() {
		this.modal.on('click', '.fluid-actions a[data-field-name]', function(e) {
			e.preventDefault()

			var wrapper = $(this).closest('.fluid-wrap'),
				fieldName = $(this).data('fieldName'),
				template = wrapper.find('.fluid-field-templates [data-field-name="'+fieldName+'"]'),
				fieldContainer = wrapper.find('.js-sorting-container')

			// Add the field
			template.clone().appendTo(fieldContainer)
			fieldContainer.find(':input').removeAttr('disabled')
			$(this).closest('li').addClass('hidden')
			that._toggleMenuItem(fieldName)

			// Close Add menu
			$(this).closest('.filters')
				.find('.open')
				.removeClass('open')
				.siblings('.sub-menu')
				.hide();

			SelectField.renderFields(fieldContainer)
		})
	},

	_bindRemoveField: function() {
		var that = this
		this.modal.on('click', '.fluid-ctrls a.fluid-remove', function(e) {
			e.preventDefault()

			var item = $(this).closest('.fluid-item')

			that._toggleMenuItem(item.data('fieldName'))
			item.remove()
		})
	},

	_toggleMenuItem(fieldName, toggle) {
		this.formContainer.find('.fluid-actions a[data-field-name="'+fieldName+'"]')
			.closest('li')
			.toggleClass('hidden', toggle)
	}
}
