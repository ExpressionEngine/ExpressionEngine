/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class MutableSelectField {
  constructor (fieldName, options) {
    this.fieldName = fieldName
    this.options = options
    this.addButton = 'a[rel="add_new"]'
    this.setField()

    this.toggleAddButton()
    this.bindAdd()
    this.bindEdit()
    this.bindRemove()
  }

  setField () {
    this.field = $('[data-input-value="'+this.fieldName+'"]')
  }

  // Don't show blue action button if there are no results
  toggleAddButton() {
    let addButtons = this.field.parent().find(this.addButton)

    if (this.field.find('.field-no-results').size()) {
      addButtons.filter((i, el) => {
        return $(el).hasClass('btn')
      }).hide()
    } else {
      addButtons.show()
    }
  }

  bindAdd () {
    this.field.parent().on('click', this.addButton, (e) => {
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
        (result) => this.handleResponse(result)
      )
    })
  }

  openForm (url) {
    EE.cp.ModalForm.openForm({
      url: url,
      createUrl: this.options.createUrl,
      load: (modal) => {
        EE.cp.form_group_toggle(modal.find('[data-group-toggle]:input:checked'))

        SelectField.renderFields(modal)
        Dropdown.renderFields(modal)

        if (this.options.onFormLoad) {
          this.options.onFormLoad(modal)
        }
      },
      success: (result) => this.handleResponse(result)
    })
  }

  handleResponse(result) {
    // A selectList key should contain the field markup
    if (result.selectList) {
      this.replaceField(result.selectList)
    // Otherwise, we have to fetch the field markup ourselves
    } else if (this.options.fieldUrl) {

      let selected = result.saveId ? [result.saveId] : []

      // Gather the current field selection so that it may be applied to the
      // field upon reload. Checkboxes for server-rendered fields, hidden
      // inputs for the React fields.
      $('input[type=checkbox][name="'+this.fieldName+'[]"]:checked, input[type=hidden][name="'+this.fieldName+'[]"]').each(function(){
          selected.push($(this).val());
      });

      let postdata = {}
      postdata[this.fieldName] = selected
      $.post(this.options.fieldUrl, postdata, (result) => {
        this.replaceField(result)
      })
    }
  }

  replaceField (html) {
    this.field.replaceWith(html)
    this.setField()
    SelectField.renderFields(this.field.parent())
    this.toggleAddButton()
  }
}
