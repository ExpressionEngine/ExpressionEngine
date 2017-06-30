/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

"use strict";

(function($) {

$(document).ready(function () {
	$('div.fields-select').each(function () {
		return new EE.SelectList($(this))
	})
})

/**
 * Construtor for select lists
 *
 * @param	{jQuery object}	field	jQuery object of .fields-select element
 */
EE.SelectList = function (field) {
	this.root = field
	this.inputs_container = $('.field-inputs:not(.js-no-results)', this.root)
	this.labels = $('label', this.inputs_container)
	this.inputs = $('input', this.inputs_container)
	this.no_results = $('.field-inputs.js-no-results', this.root)
	this.search_box = $('.filter-item__search input', this.root)
	this.selected = $('.field-input-selected', this.root)
	this.remove = $('.field-input-selected .remove a', this.root)

	this.multi = this.root.hasClass('js-multi-select')

	this.init()
}

EE.SelectList.prototype = {

	init: function() {
		this._bindSelect()
		this._bindRemove()
		this._bindDOMSearch()
	},

	/**
	 * Choice selection event
	 */
	_bindSelect: function () {
		var that = this
		this.labels.on('click',function () {

			if ( ! that.selected || this.multi) return

			$('.js-select-label', 	that.selected).text($(this).text())
			that.selected.removeClass('hidden')
		})

		this.inputs.on('click', function () {
			that.inputs.filter('[name="' + $(this).attr('name') + '"]')
			.each(function () {
				$(this).parents('label').toggleClass('act', $(this).is(':checked'))
			})
		})
	},

	/**
	 * Trash can icon click
	 */
	_bindRemove: function () {
		var that = this
		this.remove.on('click', function(e) {
			that.inputs.prop('checked', false).parents('label').removeClass('act')
			$(this).closest('.field-input-selected').addClass('hidden')

			e.preventDefault()
		})
	},

	/**
	 * Use DOM filtering to search list of choices
	 */
	_bindDOMSearch: function () {
		var that = this
		this.search_box.on('interact', function (e) {

			// No search terms, reset
			if ( ! this.value) {
				that.labels.show()
				return that._hideNoResults()
			}

			// Do the filtering
			that.labels.removeClass('hidden')
				.show()
				.not('label[data-search*="' + this.value.toLowerCase() + '"]')
				.hide()
				.addClass('hidden')

			if (that.labels.not('.hidden').size()) {
				that._hideNoResults()
			} else {
				that._showNoResults()
			}
		})
	},

	_showNoResults: function () {
		this._toggleNoResults(true)
	},

	_hideNoResults: function () {
		this._toggleNoResults(false)
	},

	_toggleNoResults: function (toggle) {
		this.inputs_container.toggleClass('hidden', toggle)
		this.no_results.toggleClass('hidden', ! toggle)
		this.root.toggleClass('field-resizable', ! toggle)
	}
}

})(jQuery);
