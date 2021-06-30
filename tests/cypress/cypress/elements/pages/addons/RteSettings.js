import ControlPanel from '../ControlPanel'

class RteSettings extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/addons/settings/rte';

        this.selectors = Object.assign(this.selectors, {
            "title": '.main-nav__title',
            "headings": 'form h1',

            // Settings View
            "default_tool_set": 'input[type!=hidden][name="rte_default_toolset"]',
            "save_settings_button": '[value="Save Settings"]',

            "create_new_button": 'a:contains("Create New")',
            "tool_sets": '.table-responsive table tr',
            "tool_set_name_header": '.table-responsive table tr th:first-child',
            "manage_header": '.table-responsive table tr th:nth-child(2)',
            "checkbox_header": '.table-responsive table tr th:nth-child(3)',

            "tool_set_names": '.table-responsive table tr td:first-child a',

            // Tool Set View
            "tool_set_name": 'input[type!=hidden][name="toolset_name"]',
            "choose_tools": 'div[data-input-value="tools"] .field-inputs input[type="checkbox"]',
            "tool_set_save_and_close_button": '.ee-main__content .form-btns-top button[value="save_and_close"]',
            "tool_set_save_button": '.ee-main__content .form-btns-top .button.button--primary'
        })
    }

    confirmSettings() {
        //this.get('breadcrumb').contains('Add-Ons')
        this.get('breadcrumb').contains('Rich Text Editor')

        cy.get('h1').contains('Rich Text Editor')

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

        this.get('default_tool_set').should('exist')
        this.get('create_new_button').should('exist')
        this.get('tool_sets').should('exist')

        this.get('tool_set_name').should('not.exist')
        this.get('choose_tools').should('not.exist')
        this.get('tool_set_save_and_close_button').should('not.exist')
    }

}

export default RteSettings;
