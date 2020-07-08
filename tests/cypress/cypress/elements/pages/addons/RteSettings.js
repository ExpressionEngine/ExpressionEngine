import ControlPanel from '../ControlPanel'

class RteSettings extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/addons/settings/rte';

        this.selectors = Object.assign(this.selectors, {
            "title": '.main-nav__title',
            "headings": 'form h1',

            // Settings View
            "rte_enabled": 'input[name=rte_enabled]',
            "rte_enabled_toggle": '[data-toggle-for=rte_enabled]',
            "default_tool_set": 'input[type!=hidden][name="rte_default_toolset_id"]',
            "save_settings_button": '.ee-main__content .form-btns-top input.btn[type="submit"]',

            "create_new_button": 'div.tbl-ctrls form fieldset.tbl-search a.btn.action',
            "tool_sets": 'div.tbl-ctrls form div.table-responsive table tr',
            "tool_set_name_header": 'div.tbl-ctrls form div.table-responsive table tr th:first-child',
            "status_header": 'div.tbl-ctrls form div.table-responsive table tr th:nth-child(2)',
            "manage_header": 'div.tbl-ctrls form div.table-responsive table tr th:nth-child(3)',
            "checkbox_header": 'div.tbl-ctrls form div.table-responsive table tr th:nth-child(4)',

            "tool_set_names": 'div.tbl-ctrls form div.table-responsive table tr td:first-child a',
            "statuses": 'div.tbl-ctrls form div.table-responsive table tr td:nth-child(2)',

            // Tool Set View
            "tool_set_name": 'input[type!=hidden][name="toolset_name"]',
            "choose_tools": 'div[data-input-value="tools"] .field-inputs input[type="checkbox"]',
            "tool_set_save_and_close_button": '.ee-main__content .form-btns-top button[value="save_and_close"]',
            "tool_set_save_button": '.ee-main__content .form-btns-top button[value="save"]'
        })
    }

    confirmSettings() {
        this.get('breadcrumb').contains('Add-Ons')
        //this.get('breadcrumb').contains('Rich Text Editor Configuration')

        this.get('headings').eq(0).contains('Rich Text Editor Configuration')
        this.get('headings').eq(1).contains('Available Tool Sets')

        this.get('rte_enabled').should('exist')
        this.get('rte_enabled_toggle').should('exist')
        this.get('default_tool_set').should('exist')
        this.get('save_settings_button').should('exist')
        this.get('create_new_button').should('exist')
        this.get('tool_sets').should('exist')

        this.get('tool_set_name').should('not.exist')
        this.get('choose_tools').should('not.exist')
    }

    confirmToolset() {
        this.get('breadcrumb').contains('Add-Ons')
        this.get('breadcrumb').contains('Rich Text Editor Configuration')
        this.get('breadcrumb').contains('RTE Tool Set')

        this.get('headings').eq(0).contains('RTE Tool Set')

        this.get('rte_enabled').should('exist')
        this.get('rte_enabled_toggle').should('exist')
        this.get('default_tool_set').should('exist')
        this.get('create_new_button').should('exist')
        this.get('tool_sets').should('exist')

        this.get('tool_set_name').should('not.exist')
        this.get('choose_tools').should('not.exist')
        this.get('tool_set_save_and_close_button').should('not.exist')
    }

}

export default RteSettings;