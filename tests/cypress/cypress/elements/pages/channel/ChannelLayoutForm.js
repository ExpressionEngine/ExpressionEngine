import ControlPanel from '../ControlPanel'

class ChannelLayoutForm extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.selectors = Object.assign(this.selectors, {
            "heading": 'div.col.w-16 div.form-standard h1',
            "add_tab_button": 'a.add-tab',

            "tabs": 'ul.tabs li a',
            "publish_tab": 'ul.tabs a[rel="t-0"]',
            "date_tab": 'ul.tabs a[rel="t-1"]',
            "hide_date_tab": 'ul.tabs a[rel="t-1"] + span',
            "categories_tab": 'ul.tabs a[rel="t-2"]',
            "hide_categories_tab": 'ul.tabs a[rel="t-2"] + span',
            "options_tab": 'ul.tabs a[rel="t-3"]',
            "hide_options_tab": 'ul.tabs a[rel="t-3"] + span',
            "fields": 'div.tab .layout-item',

            // sections : fields, 'div.tab .layout-item' do
            //     element : reorder, '.reorder'
            //     element : name, '.field-instruct > label'
            //     element : field_type, '.field-instruct > label span'
            //     element : hide, '.field-option-hide input'
            //     element : collapse, '.field-option-collapse input'
            //     element : required, '.field-option-required'
            // }

            // Layout Options
            "layout_name": 'form input[name=layout_name]',
            "member_groups": 'form input[name="member_groups[]"]',
            "submit_button": 'div.form-btns.form-btns-top button[value="save_and_close"]',

            "add_tab_modal": 'div.modal-add-new-tab',
            "add_tab_modal_tab_name": 'div.modal-add-new-tab input[name="tab_name"]',
            "add_tab_modal_submit_button": 'div.modal-add-new-tab .form-ctrls .btn',
        })
    }

    move_tool(node) {
        return node.find('.reorder')
    }

    visibiltiy_tool(node) {
        return node.all('.field-option-hide input')
    }

    minimize_tool(node) {
        return node.find('.field-option-collapse input')
    }

    field_is_required(node) {
        return node.has_selector ? ('.field-option-required') : ''
    }

    load() {
        this.create(1)
    }

    create(number) {
        cy.visit('/admin.php?/cp/channels/layouts/create/' + number)
    }

    edit(number) {
        cy.visit('/admin.php?/cp/channels/layouts/edit/' + number)
    }

}
export default ChannelLayoutForm;