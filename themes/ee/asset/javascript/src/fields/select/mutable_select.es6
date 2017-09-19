/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class MutableSelectField {
  constructor (fieldName, options) {
    this.fieldName = fieldName
    this.options = options
    this.setField()
    this.setAddButton(this.field.parent().find('a.btn.action'))

    this.bindAdd()
    this.bindEdit()
    this.bindRemove()
  }

  setField () {
    this.field = $('[data-input-value="'+this.fieldName+'"]')
  }

  setAddButton (button) {
    this.addButton = button
  }

  bindAdd () {
    this.addButton.on('click', (e) => {
      e.preventDefault()
      this.openForm(this.options.createUrl)
    })
  }

  bindEdit () {
    this.field.parent().on('click', 'label > a', (e) => {
      e.preventDefault()
      let itemId = $(e.target).closest('[data-id]').data('id')
      this.openForm(this.options.editUrl.replace('###', itemId))
    })
  }

  bindRemove () {
    this.field.parent().on('select:removeItem', '[data-id]', (e, item) => {
      EE.cp.Modal.openConfirmRemove(
        this.options.removeUrl,
        item.label,
        item.value,
        (result) => {
          this.replaceField(result.selectList)
        }
      )
    })
  }

  openForm (url) {
    EE.cp.ModalForm.openForm({
      url: url,
      load: (modal) => {
        EE.cp.form_group_toggle(modal.find('[data-group-toggle]:input:checked'))
        if (this.options.onFormLoad) {
          this.options.onFormLoad(modal)
        }
      },
      success: (result) => {
        this.replaceField(result.selectList)
      }
    })
  }

  replaceField (html) {
    this.field.replaceWith(html)
    this.setField()
    SelectField.renderFields(this.field.parent())
  }
}
