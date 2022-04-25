import ControlPanel from '../ControlPanel'
const { _, $ } = Cypress

class ChannelFieldForm extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields/create';

        this.selectors = Object.assign(this.selectors, {
            "fields": '[data-input-value*="field_channel_fields"]',
            "field_type": 'div[data-input-value="field_type"]',
            "field_type_input": 'input[name=field_type]',
            "field_type_choices": 'div[data-input-value="field_type"] .field-drop-choices label',
            "field_label": 'input[type!=hidden][name=field_label]',
            "field_name": 'input[type!=hidden][name=field_name]',
            "form": 'form[action*="admin.php?/cp/fields"]'
        })
    }

    load_edit_for_custom_field(name) {
        cy.visit('/admin.php?/cp/fields')

        cy.get('.tbl-ctrls .list-item .list-item__content:contains("' + name + '")').click();
    }

    // Create's a field given certain configuration options
    //
    // @param[Hash] a hash containing various configuration options
    //   group_id: group ID of the field group you want to add the field to, defaults to 1
    //   type: the field type, use a string that matches the item in the dropdown
    //   label: the field's label
    //   name: the field's name
    //   fields: any other fields on the page passed as a hash.key should be the name of the field, value should be the desired value
    // @yield[self] after setting any fields specified, useful for fields that
    //   _can't_ be specified for one reason or another
    createField(options = {}) {
        let defaults = {
            group_id: 1,
            fields: {}
        }
        options = Object.assign({}, defaults, options)

        cy.visit(`admin.php?/cp/fields/create/${options.group_id}`)

        this.select_field_type(options.type)

        this.get('field_label').type(options.label)
        if (options.name) {
            this.get('field_name').type(options.name)
        }
        // if options.key ? : name

        for (const field in options.fields) {
            let value = options.fields[field];
            if ($(`input[type='radio'][name='${field}']`).length) {
                cy.get(`input[type='radio'][name='${field}'][value='${value}']`).click()
            } else if ($(`input[type='checkbox'][name='${field}']`).length) {
                cy.get(`input[type='checkbox'][name='${field}'][value='${value}']`).click()
            } else if ($(`input[type!=hidden][name='${field}']`).length) {
                cy.get(`input[type!=hidden][name='${field}']`).type(value)
            } else if ($(`textarea[name='${field}']`).length) {
                cy.get(`textarea[name='${field}']`).type(value)
            } else if ($(`select[name='${field}']`).length) {
                cy.get(`select[name='${field}']`).select(value)
            }
        }


        // }

        // yield self
        // if block_given ?

        //this.get('form').find('.button[value="save"]').first().click() || Dont use for Andy
        cy.get('button[value="save"]').eq(0).click()

            // Should have some kind of alert
        this.hasAlert()
    }

    select_field_type(type) {
        this.get('field_type').find('.select__button').click({force:true})
        cy.get('div[data-input-value="field_type"] .select__dropdown .select__dropdown-item').contains(type).click({force:true})
    }

}
export default ChannelFieldForm;