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
    this.setAddButton(this.field.parent().find('a[rel="add_new"]'))

    this.toggleAddButton()
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

  // Don't show blue action button if there are no results
  toggleAddButton() {
    if (this.field.find('.field-no-results').size()) {
      this.addButton.filter((i, el) => {
        return $(el).hasClass('btn')
      }).hide()
    } else {
      this.addButton.show()
    }
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

        SelectField.renderFields(modal)
        Dropdown.renderFields(modal)

        if (this.options.onFormLoad) {
          this.options.onFormLoad(modal)
        }
      },
      success: (result) => {
        // A selectList key should contain the field markup
        if (result.selectList) {
          this.replaceField(result.selectList)
        // Otherwise, we have to fetch the field markup ourselves
        } else if (result.saveId && this.options.fieldUrl) {

          let selected = [result.saveId]

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
    })
  }

  replaceField (html) {
    this.field.replaceWith(html)
    this.setField()
    SelectField.renderFields(this.field.parent())
    this.toggleAddButton()
  }
}
