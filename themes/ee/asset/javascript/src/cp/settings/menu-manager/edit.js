/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

var MenuSets = {

	addButton: $('a[rel=modal-menu-item]'),

	init: function() {
		this.listContainer = this.addButton.parent()
		this.bindAdd()
		this.bindEdit()
		this.bindRemove()
	},

	bindAdd: function() {
		var that = this
		this.addButton.on('click', function(e) {
			e.preventDefault()
			that.openForm(EE.item_create_url)
		})
	},

	bindEdit: function() {
		var that = this
		this.listContainer.on('click', '.nestable-item label > a', function(e) {
			e.preventDefault()
			var itemId = $(this).closest('.nestable-item').data('id')
			that.openForm(EE.item_edit_url.replace('###', itemId))
		})
	},

	bindRemove: function() {
		var modal = $('.modal-menu-confirm-remove'),
			that = this

		this.listContainer.on('select:removeItem', 'li', function(e, item) {
			EE.cp.Modal.openConfirmRemove(
				'modal-menu-confirm-remove',
				'item_id',
				item.label,
				item.value,
				function(result) {
					that.replaceList(result.reorder_list)
				}
			)
		})
	},

	openForm: function(url) {
		var that = this
		EE.cp.ModalForm.openForm({
			url: url,
			load: function(modal) {
				EE.cp.form_group_toggle(modal.find('input[data-group-toggle]:checked'))
				EE.grid(document.getElementById("submenu"), EE.grid_field_settings['submenu'])
			},
			success: function(result) {
				that.replaceList(result.reorder_list)
			}
		})
	},

	replaceList: function(listHtml) {
		this.listContainer.find('[data-select-react]').remove()
		this.listContainer.prepend(listHtml)
		SelectField.renderFields(this.listContainer)
	}
}

MenuSets.init()
